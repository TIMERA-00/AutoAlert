<?php

namespace App\Models;

use Database\Factories\ChatConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'session_id', 'status', 'locale', 'closed_at'])]
class ChatConversation extends Model
{
    /** @use HasFactory<ChatConversationFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_RESOLVED = 'RESOLVED';

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('id');
    }

    #[Scope]
    protected function recent(Builder $query): Builder
    {
        return $query->orderByDesc('updated_at');
    }

    /** Resumes the open conversation of a visitor, or starts a new one. */
    public static function currentFor(?User $user, ?string $sessionId = null): self
    {
        $sessionId ??= session()->getId();

        $query = static::query()->where('status', self::STATUS_OPEN);

        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id')->where('session_id', $sessionId);
        }

        return $query->first() ?? static::create([
            'user_id' => $user?->id,
            'session_id' => Str::limit($sessionId, 64, ''),
        ]);
    }

    /** Turns a visitor conversation into a permanent one after sign-up. */
    public function attachTo(User $user): self
    {
        if ($this->user_id !== $user->id) {
            $this->forceFill(['user_id' => $user->id])->save();
        }

        return $this;
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function close(): self
    {
        $this->forceFill(['status' => self::STATUS_RESOLVED, 'closed_at' => now()])->save();

        return $this;
    }
}
