<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()
            ->favorites()
            ->with('vehicle.images')
            ->latest()
            ->paginate(30);

        return response()->json($favorites);
    }

    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        abort_unless($vehicle->isPublished(), 404);

        $existing = Favorite::where('user_id', $request->user()->id)
            ->where('vehicle_id', $vehicle->id)
            ->first();

        if (! $existing) {
            Favorite::create(['user_id' => $request->user()->id, 'vehicle_id' => $vehicle->id]);
            $vehicle->increment('favorite_count');
        }

        return response()->json(['is_favorite' => true], 201);
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        $deleted = Favorite::where('user_id', $request->user()->id)
            ->where('vehicle_id', $vehicle->id)
            ->delete();

        if ($deleted > 0) {
            $vehicle->decrement('favorite_count');
        }

        return response()->json(['is_favorite' => false]);
    }
}
