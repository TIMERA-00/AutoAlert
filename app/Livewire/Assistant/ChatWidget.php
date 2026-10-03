<?php

namespace App\Livewire\Assistant;

use App\Enums\ChatRole;
use App\Models\Alert;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Assistant\AssistantService;
use App\Services\Assistant\VehicleSearchTool;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The floating assistant. A fragment, not a page: it is injected into the
 * layout of every page, so it declares no layout of its own.
 */
class ChatWidget extends Component
{
    use WithFileUploads;

    #[Url(as: 'assistant', except: '')]
    public string $open = '';

    #[Url(as: 'ask', except: '')]
    public string $prefill = '';

    public string $message = '';

    /** @var TemporaryUploadedFile|null */
    public $photo = null;

    public bool $thinking = false;

    public ?string $notice = null;

    /** Id of the assistant message whose alert draft is awaiting a decision. */
    public ?int $pendingDraft = null;

    /** Livewire only persists public properties, so the conversation is referenced by id. */
    public ?int $conversationId = null;

    public function mount(): void
    {
        $this->conversationId = app(AssistantService::class)->conversation(auth()->user())->id;

        // A deep link such as /?ask=un+Toyota+sous+15+millions is a search in
        // itself: the visitor expects an answer, not a prefilled box.
        if ($this->prefill !== '') {
            $this->message = $this->prefill;
            $this->prefill = '';
            $this->open = '1';
            $this->send();
        }
    }

    public function toggle(): void
    {
        $this->open = $this->open === '1' ? '' : '1';
    }

    public function close(): void
    {
        $this->open = '';
        $this->message = '';
        $this->photo = null;
        $this->notice = null;
    }

    public function updatedPhoto(): void
    {
        $this->validateOnly('photo');
    }

    public function removePhoto(): void
    {
        $this->reset('photo');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        $maxKb = ((int) config('assistant.max_photo_mb', 6)) * 1024;

        return [
            'message' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:'.$maxKb],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'message' => 'Votre message',
            'photo' => 'La photo',
        ];
    }

    public function useStarter(string $value): void
    {
        $this->message = $value;
        $this->send();
    }

    public function send(): void
    {
        $content = trim($this->message);
        $hasPhoto = $this->photo !== null;

        if ($content === '' && ! $hasPhoto) {
            return;
        }

        $this->validate();

        $service = app(AssistantService::class);

        $service->recordUserMessage($this->conversation(), $content, $hasPhoto ? $this->photo : null);

        $this->reset(['message', 'photo', 'notice']);
        $this->thinking = true;

        try {
            $service->respond($this->conversation(), auth()->user());
        } catch (\Throwable $e) {
            report($e);
            $this->notice = 'Le service est momentanement indisponible. Reessayez dans un instant.';
        } finally {
            $this->thinking = false;
        }

        $this->dispatch('assistant-updated');
    }

    /** Turns the draft the assistant proposed into a real alert. */
    public function acceptDraft(?int $messageId = null)
    {
        $messageId ??= $this->latestDraftId();
        $draft = $messageId !== null ? $this->draftFor($messageId) : null;

        if ($draft === null) {
            $this->notice = "Cette proposition n'est plus disponible.";

            return;
        }

        if (! auth()->check()) {
            session()->put('alert_prefill', $this->prefillFor($draft));

            return redirect()->route('login');
        }

        try {
            $alert = app(AssistantService::class)->createAlertFromDraft(auth()->user(), $draft);
        } catch (\Throwable $e) {
            report($e);
            $this->notice = "L'alerte n'a pas pu etre enregistree.";

            return;
        }

        $this->pendingDraft = null;
        $this->notice = $alert instanceof Alert
            ? "Alerte creee. Vous serez prevenu des qu'un vehicule correspond."
            : "L'alerte n'a pas pu etre enregistree.";

        $this->dispatch('toast', message: $this->notice, type: $alert instanceof Alert ? 'success' : 'error');
    }

    public function dismissDraft(): void
    {
        $this->pendingDraft = null;
    }

    public function restart(): void
    {
        $service = app(AssistantService::class);

        if ($this->conversation()->exists) {
            $this->conversation()->close();
        }

        $this->conversationId = $service->conversation(auth()->user())->id;
        $this->pendingDraft = null;
        $this->notice = 'Nouvelle conversation.';
        $this->dispatch('toast', message: $this->notice, type: 'info');
    }

    /** Clicking a suggested vehicle opens the sheet on it. */
    public function askAbout(int $vehicleId): void
    {
        $vehicle = app(VehicleSearchTool::class)->find($vehicleId);

        if ($vehicle === null) {
            return;
        }

        $this->message = "Parlez-moi du {$vehicle->brand} {$vehicle->model}";
        $this->send();
    }

    protected function conversation(): ChatConversation
    {
        if ($this->conversationId !== null) {
            $found = ChatConversation::find($this->conversationId);

            if ($found) {
                return $found;
            }
        }

        $this->conversationId = app(AssistantService::class)->conversation(auth()->user())->id;

        return ChatConversation::findOrFail($this->conversationId);
    }

    // ------------------------------------------------------------- rendering

    /** @return Collection<int, ChatMessage> */
    #[Computed]
    public function transcript(): Collection
    {
        return $this->conversation()->messages()->get();
    }

    #[Computed]
    public function engine(): string
    {
        return app(AssistantService::class)->engineName();
    }

    /** @return array<string, string> */
    #[Computed]
    public function starters(): array
    {
        return app(AssistantService::class)->starters(auth()->user());
    }

    public function isOpen(): bool
    {
        return $this->open === '1';
    }

    public function render(): View
    {
        return view('livewire.assistant.chat-widget');
    }

    // --------------------------------------------------------------- helpers

    public function userIsAuthenticated(): bool
    {
        return auth()->check();
    }

    public function alertCount(): int
    {
        $user = auth()->user();

        return $user instanceof User ? $user->alerts()->count() : 0;
    }

    public function latestDraftId(): ?int
    {
        return $this->drafts() === [] ? null : array_key_last($this->drafts());
    }

    /**
     * Every assistant message still offering an alert, keyed by message id.
     *
     * @return array<int, array<string, mixed>>
     */
    public function drafts(): array
    {
        return $this->transcript()
            ->where('role', ChatRole::Assistant)
            ->filter(fn (ChatMessage $message) => $message->proposesAlert())
            ->mapWithKeys(fn (ChatMessage $message) => [$message->id => $message->alertDraft()])
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function draftFor(int $messageId): ?array
    {
        $draft = $this->drafts()[$messageId] ?? null;

        return is_array($draft) ? $draft : null;
    }

    /**
     * Transforms a draft into the field names the alert form expects, so a
     * visitor sent to the login page lands on a pre-filled form.
     *
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    public function prefillFor(array $draft): array
    {
        $criteria = $draft['criteria'] ?? [];

        $prefill = [
            'name' => $draft['name'] ?? null,
            'brand' => $criteria['brand'] ?? null,
            'model' => $criteria['model'] ?? null,
            'minYear' => isset($criteria['min_year']) ? (string) $criteria['min_year'] : null,
            'maxPrice' => isset($criteria['max_price']) ? (string) $criteria['max_price'] : null,
            'maxMileage' => isset($criteria['max_mileage']) ? (string) $criteria['max_mileage'] : null,
            'bodyType' => $criteria['body_type'] ?? null,
            'fuel' => $criteria['fuel'] ?? null,
            'transmission' => $criteria['transmission'] ?? null,
            'location' => $criteria['location'] ?? null,
            'frequency' => $draft['frequency'] ?? null,
        ];

        return array_filter($prefill, static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function vehiclesFor(ChatMessage $message): array
    {
        $ids = $message->vehicle_ids ?? [];

        if ($ids === []) {
            return [];
        }

        $search = app(VehicleSearchTool::class);

        return collect($ids)
            ->map(fn ($id) => $search->find((int) $id))
            ->filter()
            ->map(fn ($vehicle) => [
                'id' => $vehicle->id,
                'title' => $vehicle->title(),
                'price' => number_format($vehicle->price, 0, ',', ' ').' FCFA',
                'meta' => trim(implode(' · ', array_filter([
                    (string) $vehicle->year,
                    number_format($vehicle->mileage, 0, ',', ' ').' km',
                    $vehicle->fuel?->label(),
                    $vehicle->location,
                ]))),
                'image' => $vehicle->mainImage?->url,
                'url' => route('vehicles.show', $vehicle->slug),
            ])
            ->all();
    }
}
