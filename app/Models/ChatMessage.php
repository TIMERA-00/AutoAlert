<?php

namespace App\Models;

use App\Enums\ChatRole;
use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'chat_conversation_id', 'role', 'content', 'attachments',
    'vehicle_ids', 'engine', 'latency_ms',
])]
class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => ChatRole::class,
            'attachments' => 'array',
            'vehicle_ids' => 'array',
            'latency_ms' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }

    /** Photos sent by the visitor, read from the "files" list. */
    public function photos(): array
    {
        $files = $this->attachments['files'] ?? [];

        return collect($files)
            ->filter(fn ($file) => is_array($file) && ($file['kind'] ?? null) === 'photo')
            ->values()
            ->all();
    }

    public function hasPhotos(): bool
    {
        return $this->photos() !== [];
    }

    /** True when the assistant is offering to turn the chat into a saved alert. */
    public function proposesAlert(): bool
    {
        return ($this->attachments['alert_draft'] ?? null) !== null;
    }

    public function alertDraft(): ?array
    {
        return $this->attachments['alert_draft'] ?? null;
    }
}
