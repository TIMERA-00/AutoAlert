<?php

namespace App\Services\Assistant;

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * The single search path used by both engines, so the LLM and the offline
 * rules engine can never drift apart: the numbers shown in the chat come from
 * the same query as the public catalogue.
 */
class VehicleSearchTool
{
    /**
     * @param  array{brand?: ?string, model?: ?string, max_price?: ?int, min_price?: ?int, min_year?: ?int, max_year?: ?int, max_mileage?: ?int, body_type?: ?string, fuel?: ?string, transmission?: ?string, location?: ?string, limit?: ?int}  $criteria
     * @return Collection<int, Vehicle>
     */
    public function search(array $criteria): Collection
    {
        $limit = (int) ($criteria['limit'] ?? config('assistant.max_results', 4));

        return Vehicle::published()
            ->searchable($criteria['brand'] ?? null)
            ->filterBy([
                'brand' => $criteria['brand'] ?? null,
                'model' => $criteria['model'] ?? null,
                'minPrice' => $criteria['min_price'] ?? null,
                'maxPrice' => $criteria['max_price'] ?? null,
                'minYear' => $criteria['min_year'] ?? null,
                'maxYear' => $criteria['max_year'] ?? null,
                'maxMileage' => $criteria['max_mileage'] ?? null,
                'bodyType' => $criteria['body_type'] ?? null,
                'fuel' => $criteria['fuel'] ?? null,
                'transmission' => $criteria['transmission'] ?? null,
                'location' => $criteria['location'] ?? null,
            ])
            ->sortBy($criteria['sort'] ?? 'recent')
            ->with('images')
            ->limit(max(1, $limit))
            ->get();
    }

    public function count(array $criteria): int
    {
        return $this->search([...$criteria, 'limit' => 1000])->count();
    }

    /** The distinct brands actually present, so the model never invents one. */
    public function brands(): array
    {
        return Vehicle::published()
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->all();
    }

    public function priceRange(): array
    {
        $row = Vehicle::published()
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        return ['min' => (int) $row->min_price, 'max' => (int) $row->max_price];
    }

    /**
     * The catalogue vocabulary handed to the model, so it can only ever echo
     * a value that filters correctly.
     *
     * @return array<string, mixed>
     */
    public function vocabulary(): array
    {
        return [
            'marques_disponibles' => $this->brands(),
            'gammes' => BodyType::options(),
            'carburants' => FuelType::options(),
            'boites' => Transmission::options(),
            'fourchette_prix' => $this->priceRange(),
        ];
    }

    /**
     * JSON payload for the model. Deliberately compact: the tokens spent on
     * vehicle rows are the ones not available for the actual conversation.
     *
     * @param  Collection<int, Vehicle>  $vehicles
     * @return array<int, array<string, mixed>>
     */
    public function describe(Collection $vehicles): array
    {
        return $vehicles->map(fn (Vehicle $vehicle) => [
            'id' => $vehicle->id,
            'titre' => $vehicle->title(),
            'prix' => $vehicle->price,
            'annee' => $vehicle->year,
            'kilometrage' => $vehicle->mileage,
            'carburant' => $vehicle->fuel?->label(),
            'boite' => $vehicle->transmission?->label(),
            'carrosserie' => $vehicle->body_type?->label(),
            'ville' => $vehicle->location,
            'couleur' => $vehicle->color,
        ])->all();
    }

    public function find(int $id): ?Vehicle
    {
        return Vehicle::published()->with('images')->find($id);
    }
}
