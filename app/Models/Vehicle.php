<?php

namespace App\Models;

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'source_id', 'source_url', 'brand', 'model', 'year', 'price', 'mileage',
    'fuel', 'transmission', 'body_type', 'color', 'location', 'description',
    'status', 'reference', 'slug',
])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => VehicleStatus::class,
            'fuel' => FuelType::class,
            'transmission' => Transmission::class,
            'body_type' => BodyType::class,
            'published_at' => 'datetime',
            'year' => 'integer',
            'price' => 'integer',
            'mileage' => 'integer',
            'view_count' => 'integer',
            'favorite_count' => 'integer',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    /** URLs are SEO friendly: /vehicles/toyota-rav4-2023-00042 */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getRouteKey(): string
    {
        return $this->slug ?: (string) $this->id;
    }

    public function buildSlug(): string
    {
        return Str::slug(trim("{$this->brand}-{$this->model}-{$this->year}"))
            .($this->reference ? '-'.$this->reference : '');
    }

    protected static function booted(): void
    {
        static::saving(function (self $vehicle): void {
            $base = $vehicle->buildSlug();
            $slug = $base;
            $suffix = 1;

            while (
                static::query()
                    ->where('slug', $slug)
                    ->when($vehicle->exists, fn ($q) => $q->whereKeyNot($vehicle->getKey()))
                    ->exists()
            ) {
                $slug = $base.'-'.(++$suffix);
            }

            $vehicle->slug = $slug;
        });
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function sourceClicks(): HasMany
    {
        return $this->hasMany(SourceClick::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class);
    }

    /** Accessor (pas une relation) : renvoie l'image principale du vehicule. */
    protected function mainImage(): Attribute
    {
        return Attribute::get(fn (): ?VehicleImage => $this->images->first());
    }

    public function title(): string
    {
        return "{$this->brand} {$this->model} {$this->year}";
    }

    public function isPublished(): bool
    {
        return $this->status === VehicleStatus::Published;
    }

    public function publish(): bool
    {
        return $this->status->isPublic();
    }

    public function markAsPublished(): self
    {
        $this->status = VehicleStatus::Published;
        $this->published_at ??= now();

        return $this;
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    // ---------------------------------------------------------------- scopes

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', VehicleStatus::Published);
    }

    #[Scope]
    protected function searchable(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('brand', 'like', "%{$term}%")
                ->orWhere('model', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%");
        });
    }

    #[Scope]
    protected function filterBy(Builder $query, array $filters): Builder
    {
        $has = static fn (string $key): bool => array_key_exists($key, $filters)
            && $filters[$key] !== null
            && $filters[$key] !== '';

        return $query
            ->when(filled($filters['brand'] ?? null), fn (Builder $q) => $q->where('brand', 'like', $filters['brand'].'%'))
            ->when(filled($filters['model'] ?? null), fn (Builder $q) => $q->where('model', 'like', '%'.$filters['model'].'%'))
            ->when($has('minPrice'), fn (Builder $q) => $q->where('price', '>=', $filters['minPrice']))
            ->when($has('maxPrice'), fn (Builder $q) => $q->where('price', '<=', $filters['maxPrice']))
            ->when($has('minYear'), fn (Builder $q) => $q->where('year', '>=', $filters['minYear']))
            ->when($has('maxYear'), fn (Builder $q) => $q->where('year', '<=', $filters['maxYear']))
            ->when($has('maxMileage'), fn (Builder $q) => $q->where('mileage', '<=', $filters['maxMileage']))
            ->when(filled($filters['fuel'] ?? null), fn (Builder $q) => $q->whereIn('fuel', (array) $filters['fuel']))
            ->when(filled($filters['transmission'] ?? null), fn (Builder $q) => $q->whereIn('transmission', (array) $filters['transmission']))
            ->when(filled($filters['bodyType'] ?? null), fn (Builder $q) => $q->whereIn('body_type', (array) $filters['bodyType']))
            ->when(filled($filters['location'] ?? null), fn (Builder $q) => $q->where('location', 'like', '%'.$filters['location'].'%'))
            ->when($has('sourceId'), fn (Builder $q) => $q->where('source_id', $filters['sourceId']))
            ->when($has('status'), fn (Builder $q) => $q->where('status', $filters['status']));
    }

    #[Scope]
    protected function sortBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('price')->orderByDesc('created_at'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('created_at'),
            'year_desc' => $query->orderByDesc('year')->orderBy('price'),
            'mileage_asc' => $query->orderBy('mileage')->orderByDesc('created_at'),
            default => $query->orderByDesc('published_at')->orderByDesc('created_at'),
        };
    }

    /** Distinct values used to build the filter panel. */
    public static function facets(bool $includeAll = false): array
    {
        $base = static::query()->when(! $includeAll, fn (Builder $q) => $q->published());

        return [
            'brands' => (clone $base)->select('brand')->selectRaw('COUNT(*) as aggregate')->groupBy('brand')->orderByDesc('aggregate')->limit(40)->pluck('brand')->all(),
            'locations' => (clone $base)->whereNotNull('location')->select('location')->selectRaw('COUNT(*) as aggregate')->groupBy('location')->orderByDesc('aggregate')->limit(30)->pluck('location')->all(),
            'price' => (clone $base)->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first(),
            'year' => (clone $base)->selectRaw('MIN(year) as min_year, MAX(year) as max_year')->first(),
        ];
    }
}
