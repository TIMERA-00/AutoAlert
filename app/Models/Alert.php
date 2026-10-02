<?php

namespace App\Models;

use App\Enums\AlertFrequency;
use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'name', 'brand', 'model', 'min_year', 'max_year', 'min_price', 'max_price',
    'max_mileage', 'fuel', 'transmission', 'body_type', 'location', 'frequency', 'is_active',
])]
class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fuel' => FuelType::class,
            'transmission' => Transmission::class,
            'body_type' => BodyType::class,
            'frequency' => AlertFrequency::class,
            'is_active' => 'boolean',
            'min_year' => 'integer',
            'max_year' => 'integer',
            'min_price' => 'integer',
            'max_price' => 'integer',
            'max_mileage' => 'integer',
            'last_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function hasCriteria(): bool
    {
        return collect([
            $this->brand, $this->model, $this->min_year, $this->max_year,
            $this->min_price, $this->max_price, $this->max_mileage,
            $this->fuel, $this->transmission, $this->body_type, $this->location,
        ])->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
    }

    public function summary(): string
    {
        $parts = [];
        if ($this->brand) {
            $parts[] = trim($this->brand.' '.$this->model);
        }
        if ($this->min_year) {
            $parts[] = "{$this->min_year}+";
        }
        if ($this->max_price) {
            $parts[] = '<= '.$this->max_price.' FCFA';
        }
        if ($this->max_mileage) {
            $parts[] = '<= '.number_format($this->max_mileage, 0, ',', ' ').' km';
        }
        if ($this->fuel) {
            $parts[] = $this->fuel->label();
        }
        if ($this->transmission) {
            $parts[] = $this->transmission->label();
        }
        if ($this->body_type) {
            $parts[] = $this->body_type->label();
        }
        if ($this->location) {
            $parts[] = $this->location;
        }

        return $parts === [] ? 'Tous les vehicules' : implode(' - ', $parts);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
