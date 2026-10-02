<?php

namespace App\Livewire\Favorites;

use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class FavoritesList extends Component
{
    use WithPagination;

    #[Url(as: 'tri', except: 'recent')]
    public string $sort = 'recent';

    public function remove(int $vehicleId): void
    {
        $vehicle = auth()->user()->favorites()->where('vehicle_id', $vehicleId)->first();

        if ($vehicle) {
            $vehicle->delete();
            Vehicle::whereKey($vehicleId)->decrement('favorite_count');
        }
    }

    public function render(): View
    {
        $query = auth()->user()
            ->favorites()
            ->with('vehicle.images')
            ->getQuery()
            ->select('favorites.*')
            ->join('vehicles', 'vehicles.id', '=', 'favorites.vehicle_id')
            ->orderByDesc('favorites.created_at');

        match ($this->sort) {
            'price_asc' => $query->orderBy('vehicles.price'),
            'price_desc' => $query->orderByDesc('vehicles.price'),
            'year_desc' => $query->orderByDesc('vehicles.year'),
            'mileage_asc' => $query->orderBy('vehicles.mileage'),
            default => null,
        };

        return view('livewire.favorites.favorites-list', [
            'favorites' => $query->paginate(9),
        ])->title('Mes favoris | AutoAlert');
    }
}
