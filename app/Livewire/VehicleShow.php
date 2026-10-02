<?php

namespace App\Livewire;

use App\Enums\AlertFrequency;
use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\NotificationChannel;
use App\Enums\Transmission;
use App\Models\Vehicle;
use App\Services\PageViewTracker;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class VehicleShow extends Component
{
    public Vehicle $vehicle;

    public bool $isFavorite = false;

    public int $activeImage = 0;

    public function mount(Vehicle $vehicle): void
    {
        abort_unless($vehicle->isPublished() || (auth()->check() && auth()->user()->isAdmin()), 404);

        $this->vehicle = $vehicle->load(['images', 'source']);
        $this->vehicle->incrementViews();

        $this->isFavorite = auth()->check()
            && auth()->user()->favorites()->where('vehicle_id', $vehicle->id)->exists();

        app(PageViewTracker::class)->record($vehicle);
    }

    public function toggleFavorite(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $user = auth()->user();
        $existing = $user->favorites()->where('vehicle_id', $this->vehicle->id)->first();

        if ($existing) {
            $existing->delete();
            $this->isFavorite = false;
            $this->vehicle->decrement('favorite_count');

            return;
        }

        $user->favorites()->create(['vehicle_id' => $this->vehicle->id]);
        $this->isFavorite = true;
        $this->vehicle->increment('favorite_count');
    }

    public function trackSourceClick(): void
    {
        $this->vehicle->sourceClicks()->create(['user_id' => auth()->id()]);
    }

    /** @return array<string, string> */
    public function getShareLinksProperty(): array
    {
        $url = route('vehicles.show', $this->vehicle);
        $title = $this->vehicle->title().' - '.number_format($this->vehicle->price, 0, ',', ' ').' FCFA';

        return [
            'whatsapp' => 'https://wa.me/?text='.rawurlencode($title.' '.$url),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url),
            'x' => 'https://twitter.com/intent/tweet?text='.rawurlencode($title).'&url='.rawurlencode($url),
            'copy' => $url,
        ];
    }

    /** Vehicles with similar characteristics (Phase 3 : recommandations). */
    public function getSimilarProperty()
    {
        return Vehicle::published()
            ->whereKeyNot($this->vehicle->id)
            ->when($this->vehicle->brand, fn ($q) => $q->where('brand', $this->vehicle->brand))
            ->when(! $this->vehicle->brand, fn ($q) => $q->where('body_type', $this->vehicle->body_type))
            ->with('images')
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();
    }

    public function render(): View
    {
        $view = view('livewire.vehicle-show', [
            'similar' => $this->getSimilarProperty(),
            'fuelOptions' => FuelType::options(),
            'transmissionOptions' => Transmission::options(),
            'bodyTypeOptions' => BodyType::options(),
            'frequencyOptions' => AlertFrequency::options(),
            'channelOptions' => NotificationChannel::options(),
        ]);

        $view->title("{$this->vehicle->title()} - ".number_format($this->vehicle->price, 0, ',', ' ').' FCFA | AutoAlert');

        return $view;
    }
}
