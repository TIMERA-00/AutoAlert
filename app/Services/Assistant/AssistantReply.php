<?php

namespace App\Services\Assistant;

use App\Models\Vehicle;

/**
 * What an engine returns. Vehicles are carried alongside the text so the
 * widget can render real cards instead of asking the model to describe them.
 */
class AssistantReply
{
    /**
     * @param  array<int, Vehicle>  $vehicles
     * @param  array<string, mixed>|null  $alertDraft
     * @param  array<string, mixed>|null  $identification
     */
    public function __construct(
        public readonly string $content,
        public readonly array $vehicles = [],
        public readonly ?array $alertDraft = null,
        public readonly ?array $identification = null,
        public readonly string $engine = 'rules',
        public readonly int $latencyMs = 0,
        public readonly array $criteria = [],
    ) {}

    public function vehicleIds(): array
    {
        return array_map(fn (Vehicle $v) => $v->id, $this->vehicles);
    }

    /** @return array<string, mixed> */
    public function toAttachments(): array
    {
        $attachments = [];

        if ($this->alertDraft !== null) {
            $attachments['alert_draft'] = $this->alertDraft;
        }

        if ($this->identification !== null) {
            $attachments['identification'] = $this->identification;
        }

        if ($this->criteria !== []) {
            $attachments['criteria'] = $this->criteria;
        }

        return $attachments;
    }
}
