<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * Query-parameter aliases accepted by the mobile clients, so that
     * `max_price`, `maxPrice` and `prix_max` all reach the same filter.
     */
    private const FILTER_ALIASES = [
        'q' => ['q', 'search', 'recherche'],
        'brand' => ['brand', 'marque'],
        'model' => ['model', 'modele'],
        'minPrice' => ['minPrice', 'min_price', 'prix_min'],
        'maxPrice' => ['maxPrice', 'max_price', 'prix_max'],
        'minYear' => ['minYear', 'min_year', 'annee_min'],
        'maxYear' => ['maxYear', 'max_year', 'annee_max'],
        'maxMileage' => ['maxMileage', 'max_mileage', 'kilometrage_max'],
        'fuel' => ['fuel', 'carburant'],
        'transmission' => ['transmission', 'boite'],
        'bodyType' => ['bodyType', 'body_type', 'carrosserie'],
        'location' => ['location', 'ville', 'localisation'],
    ];

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('pageSize', $request->input('per_page', 20)), 60);

        $vehicles = Vehicle::published()
            ->searchable($this->filter($request, 'q'))
            ->filterBy([
                'brand' => $this->filter($request, 'brand'),
                'model' => $this->filter($request, 'model'),
                'minPrice' => $this->intFilter($request, 'minPrice'),
                'maxPrice' => $this->intFilter($request, 'maxPrice'),
                'minYear' => $this->intFilter($request, 'minYear'),
                'maxYear' => $this->intFilter($request, 'maxYear'),
                'maxMileage' => $this->intFilter($request, 'maxMileage'),
                'fuel' => $this->filter($request, 'fuel'),
                'transmission' => $this->filter($request, 'transmission'),
                'bodyType' => $this->filter($request, 'bodyType'),
                'location' => $this->filter($request, 'location'),
            ])
            ->sortBy($request->input('sort', 'recent'))
            ->with('images')
            ->paginate($perPage);

        return response()->json($vehicles);
    }

    private function filter(Request $request, string $key): ?string
    {
        foreach (self::FILTER_ALIASES[$key] as $alias) {
            $value = $request->input($alias);

            if (filled($value)) {
                return is_string($value) ? $value : null;
            }
        }

        return null;
    }

    private function intFilter(Request $request, string $key): ?int
    {
        $value = $this->filter($request, $key);

        return $value === null ? null : (int) $value;
    }

    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        abort_if($vehicle->status !== VehicleStatus::Published, 404);

        $vehicle->incrementViews();

        return response()->json([
            'data' => $vehicle->load(['images', 'source']),
            'is_favorite' => $request->user()?->favorites()->where('vehicle_id', $vehicle->id)->exists() ?? false,
        ]);
    }

    public function facets(): JsonResponse
    {
        return response()->json(Vehicle::facets());
    }
}
