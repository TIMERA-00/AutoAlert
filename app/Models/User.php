<?php

namespace App\Models;

use App\Enums\AlertFrequency;
use App\Enums\NotificationChannel;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'first_name', 'last_name', 'email', 'password', 'phone', 'role',
    'is_active', 'notify_email', 'notify_whatsapp', 'notify_in_app', 'default_frequency',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'notify_email' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'notify_in_app' => 'boolean',
            'default_frequency' => AlertFrequency::class,
        ];
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** Accessor (pas une relation) : initiales affichees dans la navigation. */
    protected function initials(): Attribute
    {
        return Attribute::get(
            fn (): string => mb_strtoupper(mb_substr($this->first_name ?? '', 0, 1).mb_substr($this->last_name ?? '', 0, 1))
        );
    }

    /** Keeps Breeze views working with the split first/last name fields. */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->fullName());
    }

    /** @return array<int, NotificationChannel> */
    public function enabledChannels(): array
    {
        return array_values(array_filter([
            $this->notify_email ? NotificationChannel::Email : null,
            $this->notify_whatsapp && $this->phone ? NotificationChannel::Whatsapp : null,
            $this->notify_in_app ? NotificationChannel::InApp : null,
        ]));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }
}
