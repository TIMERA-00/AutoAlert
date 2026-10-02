<?php

namespace App\Livewire\Admin;

use App\Enums\VehicleStatus;
use App\Jobs\MatchAlertsForVehicle;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class VehicleTable extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    #[Url(as: 'tri', except: 'recent')]
    public string $sort = 'recent';

    public function changeStatus(int $vehicleId, string $status): void
    {
        $vehicle = Vehicle::find($vehicleId);

        if (! $vehicle || ! $vehicle->status->canTransitionTo(VehicleStatus::from($status))) {
            $this->dispatch('toast', message: 'Transition de statut non autorisee.', type: 'error');

            return;
        }

        $this->applyStatus($vehicle, VehicleStatus::from($status));
    }

    public function publish(int $vehicleId): void
    {
        $this->applyStatus(Vehicle::findOrFail($vehicleId), VehicleStatus::Published);
    }

    public function delete(int $vehicleId): void
    {
        Vehicle::findOrFail($vehicleId)->delete();

        $this->dispatch('toast', message: 'Vehicule supprime.', type: 'success');
    }

    private function applyStatus(Vehicle $vehicle, VehicleStatus $status): void
    {
        $vehicle->status = $status;
        $vehicle->published_at ??= $status === VehicleStatus::Published ? now() : $vehicle->published_at;
        $vehicle->save();

        if ($status === VehicleStatus::Published) {
            // Alert matching + notifications happen in the queue.
            MatchAlertsForVehicle::dispatch($vehicle->id);
            $this->dispatch('toast', message: 'Vehicule publie : les alertes correspondantes sont en cours de traitement.', type: 'success');

            return;
        }

        $this->dispatch('toast', message: "Statut mis a jour : {$status->label()}.", type: 'success');
    }

    public function render(): View
    {
        $query = Vehicle::query()
            ->with(['images', 'source'])
            ->searchable($this->search)
            ->when($this->status !== '', fn (Builder $q) => $q->where('status', $this->status))
            ->filterBy(['brand' => null])
            ->sortBy($this->sort);

        $counts = Vehicle::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.admin.vehicle-table', [
            'vehicles' => $query->paginate(15),
            'counts' => $counts,
            'total' => Vehicle::count(),
            'statusOptions' => VehicleStatus::options(),
        ])->title('Administration - vehicules | AutoAlert');
    }
}
