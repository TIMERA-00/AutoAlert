<?php

namespace App\Services\Assistant;

use App\Enums\ChatRole;
use App\Enums\NotificationChannel;
use App\Enums\VehicleSort;
use App\Models\Alert;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Entry point of the widget: owns the conversation, picks the engine, stores
 * photos and turns a draft into a real alert.
 */
class AssistantService
{
    public function __construct(
        private readonly OpenAiClient $client,
        private readonly OpenAiEngine $openAi,
        private readonly RulesEngine $rules,
        private readonly VehicleSearchTool $search,
        private readonly VehicleIdentifier $identifier,
        private readonly ImageStorageService $images,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('assistant.enabled', true);
    }

    public function engineName(): string
    {
        return $this->client->driver();
    }

    public function conversation(?User $user): ChatConversation
    {
        return ChatConversation::currentFor($user);
    }

    /** Moves a guest conversation onto the account after sign-up or sign-in. */
    public function adoptFor(User $user): ChatConversation
    {
        $visitorId = session()->getId();

        $guest = ChatConversation::query()
            ->whereNull('user_id')
            ->where('session_id', $visitorId)
            ->where('status', ChatConversation::STATUS_OPEN)
            ->latest('id')
            ->first();

        if ($guest) {
            return $guest->attachTo($user);
        }

        return ChatConversation::currentFor($user);
    }

    /** @return array<int, ChatMessage> */
    private function history(ChatConversation $conversation): Collection
    {
        return $conversation->messages()
            ->limit((int) config('assistant.max_history', 20))
            ->get();
    }

    /**
     * Criteria already agreed earlier in the conversation, so "et automatique ?"
     * keeps the brand and the budget already mentioned.
     *
     * @return array<string, mixed>
     */
    private function knownCriteria(Collection $messages): array
    {
        $criteria = [];

        foreach ($messages->where('role', ChatRole::Assistant) as $message) {
            $criteria = array_merge($criteria, $message->attachments['criteria'] ?? []);
        }

        return $criteria;
    }

    private function context(ChatConversation $conversation, ?User $user): AssistantContext
    {
        $history = $this->history($conversation);

        return new AssistantContext(
            conversation: $conversation,
            history: $history,
            knownCriteria: $this->knownCriteria($history),
            identifiedVehicles: $history
                ->map(fn (ChatMessage $m) => $m->attachments['identification'] ?? null)
                ->filter()
                ->map(fn (array $i) => array_filter([
                    'brand' => $i['brand'] ?? null,
                    'model' => $i['model'] ?? null,
                    'year' => $i['year'] ?? null,
                ]))
                ->values()
                ->all(),
            user: $user,
        );
    }

    /** Records the visitor's turn, storing any attached photo. */
    public function recordUserMessage(ChatConversation $conversation, string $content, ?UploadedFile $photo = null): ChatMessage
    {
        $attachments = [];

        if ($photo) {
            $attachments[] = $this->storePhoto($photo);
        }

        $message = $conversation->messages()->create([
            'role' => ChatRole::User,
            'content' => trim($content),
            'attachments' => $attachments !== [] ? ['files' => $attachments] : null,
        ]);

        // A photo-only message is a request in itself.
        if (trim($content) === '' && $photo) {
            $message->update(['content' => 'Photo de ma voiture']);
        }

        return $message;
    }

    /** @return array<string, mixed> */
    private function storePhoto(UploadedFile $photo): array
    {
        try {
            $stored = $this->images->store($photo, 'assistant');
        } catch (\Throwable $e) {
            Log::warning('Assistant photo upload failed', ['message' => $e->getMessage()]);

            $stored = ['url' => $photo->getRealPath() ?: null, 'provider' => 'temporary'];
        }

        return [
            'kind' => 'photo',
            'url' => $stored['url'] ?? null,
            'path' => $stored['path'] ?? ($stored['url'] ?? null),
            'provider' => $stored['provider'] ?? 'local',
            'name' => $photo->getClientOriginalName(),
        ];
    }

    /** Produces the assistant's turn for whatever the visitor just sent. */
    public function respond(ChatConversation $conversation, ?User $user): AssistantReply
    {
        $messages = $conversation->messages()->orderBy('id')->get();
        $lastUserMessage = $messages->where('role', ChatRole::User)->last();
        $lastAssistant = $messages->where('role', ChatRole::Assistant)->last();

        // Ignore the request if we already answered that exact turn. Ordering
        // by id, not by timestamp: two messages written in the same second
        // would otherwise make a fresh question look already answered.
        if ($lastAssistant && $lastUserMessage && $lastAssistant->id > $lastUserMessage->id) {
            return new AssistantReply($lastAssistant->content, engine: 'cached');
        }

        $context = $this->context($conversation, $user);

        // The visitor only sent a photo: identify it and describe the result.
        if ($lastUserMessage?->hasPhotos()) {
            $photo = $lastUserMessage->photos()[0];
            $identification = $this->identifier->identify($photo['path'] ?? '');
            $this->identifier->logResult($photo['path'] ?? '', $identification);

            return $this->persist($conversation, $this->rules->identificationReply($identification));
        }

        $reply = $this->client->driver() === 'openai'
            ? $this->openAi->reply((string) $lastUserMessage?->content, $context)
            : $this->rules->reply((string) $lastUserMessage?->content, $context);

        return $this->persist($conversation, $reply);
    }

    private function persist(ChatConversation $conversation, AssistantReply $reply): AssistantReply
    {
        $conversation->messages()->create([
            'role' => ChatRole::Assistant,
            'content' => $reply->content,
            'attachments' => $reply->toAttachments() ?: null,
            'vehicle_ids' => $reply->vehicleIds(),
            'engine' => $reply->engine,
            'latency_ms' => $reply->latencyMs,
        ]);

        $conversation->touch();

        return $reply;
    }

    /** Turns a draft proposed by the assistant into a saved alert. */
    public function createAlertFromDraft(User $user, array $draft): ?Alert
    {
        $engine = app(OpenAiEngine::class);

        $criteria = array_filter($draft['criteria'] ?? [], static fn ($v) => $v !== null && $v !== '');

        // A budget stated as "15 millions" must land as an integer column.
        foreach (['max_price', 'min_price', 'min_year', 'max_mileage'] as $key) {
            if (isset($criteria[$key])) {
                $criteria[$key] = (int) $criteria[$key];
            }
        }

        return $engine->createAlert($user, [...$draft, 'criteria' => $criteria]);
    }

    /** Suggestions shown before the visitor has typed anything. */
    public function starters(?User $user): array
    {
        $range = $this->search->priceRange();
        $brands = collect($this->search->brands())->take(3);

        return [
            'budget' => 'Vous avez un budget maximum, comment je filtre ?',
            $brands->first() !== null ? 'marque' : 'catalogue' => $brands->isNotEmpty()
                ? sprintf('Montrez-moi les %s disponibles', $brands->first())
                : 'Montrez-moi le catalogue',
            'photo' => 'Envoyer une photo',
            'alert' => 'Creer une alerte',
            'hints' => sprintf('Budget moyen : %s FCFA', number_format(intdiv($range['min'] + $range['max'], 2), 0, ',', ' ')),
        ];
    }

    /** Channel preference used when the visitor accepts a draft without choosing. */
    public function channelsFor(?User $user): array
    {
        return $user?->enabledChannels()
            ?? collect(config('assistant.alert.channels'))
                ->map(fn (string $c) => NotificationChannel::from($c))
                ->all();
    }

    public function sortOptions(): array
    {
        return VehicleSort::options();
    }

    public function normalize(string $text): string
    {
        return Str::limit(trim($text), 2_000);
    }
}
