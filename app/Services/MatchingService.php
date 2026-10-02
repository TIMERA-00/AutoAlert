<?php

namespace App\Services;

use App\Enums\AlertFrequency;
use App\Models\Alert;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Deterministic matching between alerts and vehicles.
 *
 * Every criterion set by the user must be satisfied (AND logic).
 * A null/empty criterion means "no constraint" and is ignored.
 */
class MatchingService
{
    /** Normalises text for accent/case-insensitive comparisons. */
    public static function normalize(?string $value): string
    {
        return mb_strtolower(trim((string) $value), 'UTF-8');
    }

    private static function contains(?string $haystack, ?string $needle): bool
    {
        if ($needle === null || $needle === '') {
            return true;
        }

        return str_contains(self::normalize($haystack), self::normalize($needle));
    }

    private static function enumMatches(mixed $alertValue, mixed $vehicleValue): bool
    {
        if ($alertValue === null || $alertValue === '') {
            return true;
        }

        return $alertValue === $vehicleValue;
    }

    /**
     * @return array{matched: bool, score: int, reasons: array<int, string>}
     */
    public function evaluate(Alert $alert, Vehicle $vehicle): array
    {
        $checks = [
            'marque' => self::contains($vehicle->brand, $alert->brand),
            'modele' => self::contains($vehicle->model, $alert->model),
            'annee min' => $alert->min_year === null || $vehicle->year >= $alert->min_year,
            'annee max' => $alert->max_year === null || $vehicle->year <= $alert->max_year,
            'prix min' => $alert->min_price === null || $vehicle->price >= $alert->min_price,
            'prix max' => $alert->max_price === null || $vehicle->price <= $alert->max_price,
            'kilometrage max' => $alert->max_mileage === null || $vehicle->mileage <= $alert->max_mileage,
            'carburant' => self::enumMatches($alert->fuel, $vehicle->fuel),
            'boite' => self::enumMatches($alert->transmission, $vehicle->transmission),
            'carrosserie' => self::enumMatches($alert->body_type, $vehicle->body_type),
            'lieu' => self::contains($vehicle->location, $alert->location),
        ];

        $failed = array_keys(array_filter($checks, fn (bool $ok) => ! $ok));
        if ($failed !== []) {
            return ['matched' => false, 'score' => 0, 'reasons' => []];
        }

        return [
            'matched' => true,
            'score' => $this->relevanceScore($alert, $vehicle),
            'reasons' => array_keys(array_filter($checks)),
        ];
    }

    public function matches(Alert $alert, Vehicle $vehicle): bool
    {
        return $this->evaluate($alert, $vehicle)['matched'];
    }

    /** 40-100: how strongly the vehicle fits the alert (closer bounds = better score). */
    private function relevanceScore(Alert $alert, Vehicle $vehicle): int
    {
        $score = 100;

        if ($alert->brand && self::normalize($alert->brand) !== self::normalize($vehicle->brand)) {
            $score -= 15;
        }
        if ($alert->model && self::normalize($alert->model) !== self::normalize($vehicle->model)) {
            $score -= 10;
        }
        if ($alert->max_price && $alert->max_price > 0) {
            $ratio = $vehicle->price / $alert->max_price;
            if ($ratio > 0.9) {
                $score -= 10;
            }
        }
        if ($alert->max_mileage && $alert->max_mileage > 0) {
            if ($vehicle->mileage / $alert->max_mileage > 0.9) {
                $score -= 10;
            }
        }
        if ($alert->min_year && $vehicle->year - $alert->min_year > 3) {
            $score -= 5;
        }

        return max(min($score, 100), 40);
    }

    /**
     * Alerts that should be notified right now for a freshly published vehicle.
     *
     * @return Collection<int, array{alert: Alert, score: int}>
     */
    public function matchingAlertsFor(Vehicle $vehicle, ?int $limit = null): Collection
    {
        if (! $vehicle->isPublished()) {
            return collect();
        }

        return Alert::query()
            ->active()
            ->with('user')
            ->get()
            ->map(fn (Alert $alert) => ['alert' => $alert, 'result' => $this->evaluate($alert, $vehicle)])
            ->filter(fn (array $row) => $row['result']['matched'])
            ->sortByDesc(fn (array $row) => $row['result']['score'])
            ->take($limit)
            ->values()
            ->map(fn (array $row) => ['alert' => $row['alert'], 'score' => $row['result']['score']]);
    }

    /** Vehicles currently matching an alert (used by the alert preview). */
    public function vehiclesFor(Alert $alert, int $limit = 12): Collection
    {
        return Vehicle::query()
            ->published()
            ->when($alert->brand, fn ($q) => $q->where('brand', 'like', "{$alert->brand}%"))
            ->when($alert->model, fn ($q) => $q->where('model', 'like', "%{$alert->model}%"))
            ->when($alert->min_year, fn ($q) => $q->where('year', '>=', $alert->min_year))
            ->when($alert->max_year, fn ($q) => $q->where('year', '<=', $alert->max_year))
            ->when($alert->min_price, fn ($q) => $q->where('price', '>=', $alert->min_price))
            ->when($alert->max_price, fn ($q) => $q->where('price', '<=', $alert->max_price))
            ->when($alert->max_mileage, fn ($q) => $q->where('mileage', '<=', $alert->max_mileage))
            ->when($alert->fuel, fn ($q) => $q->where('fuel', $alert->fuel))
            ->when($alert->transmission, fn ($q) => $q->where('transmission', $alert->transmission))
            ->when($alert->body_type, fn ($q) => $q->where('body_type', $alert->body_type))
            ->when($alert->location, fn ($q) => $q->where('location', 'like', "%{$alert->location}%"))
            ->orderByDesc('published_at')
            ->with('images')
            ->limit($limit)
            ->get();
    }

    public function countMatches(Alert $alert): int
    {
        return $this->vehiclesFor($alert, 1000)->count();
    }

    public function frequency(Alert $alert): AlertFrequency
    {
        return $alert->frequency ?? AlertFrequency::Immediate;
    }
}
