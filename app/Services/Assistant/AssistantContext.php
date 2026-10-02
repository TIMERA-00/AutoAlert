<?php

namespace App\Services\Assistant;

use App\Enums\AlertFrequency;
use App\Enums\NotificationChannel;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Shared state passed to whichever engine is active, so both produce answers
 * grounded in exactly the same conversation.
 */
class AssistantContext
{
    /**
     * @param  Collection<int, ChatMessage>  $history
     * @param  array<string, mixed>  $knownCriteria  Criteria accumulated earlier in the chat.
     * @param  array<int, Vehicle>  $identifiedVehicles  Cars recognised from the visitor's photos.
     */
    public function __construct(
        public readonly ChatConversation $conversation,
        public readonly Collection $history,
        public readonly array $knownCriteria = [],
        public readonly array $identifiedVehicles = [],
        public readonly ?User $user = null,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function transcript(): array
    {
        return $this->history
            ->filter(fn (ChatMessage $m) => $m->content !== '' && $m->role->value !== 'SYSTEM')
            ->map(fn (ChatMessage $m) => ['role' => $m->role->value, 'content' => $m->content])
            ->values()
            ->all();
    }

    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    public function defaultFrequency(): AlertFrequency
    {
        $raw = $this->user?->default_frequency ?? config('assistant.alert.default_frequency');

        return $raw instanceof AlertFrequency ? $raw : AlertFrequency::from($raw);
    }

    /** @return array<int, NotificationChannel> */
    public function defaultChannels(): array
    {
        return $this->user?->enabledChannels()
            ?? collect(config('assistant.alert.channels'))
                ->map(fn (string $c) => NotificationChannel::from($c))
                ->all();
    }
}
