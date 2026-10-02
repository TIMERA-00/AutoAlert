<?php

namespace App\Livewire\Alerts;

use App\Enums\AlertFrequency;
use App\Services\MatchingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AlertsList extends Component
{
    public string $search = '';

    public function toggle(int $alertId): void
    {
        $alert = auth()->user()->alerts()->find($alertId);

        $alert?->update(['is_active' => ! $alert->is_active]);
    }

    public function delete(int $alertId): void
    {
        auth()->user()->alerts()->find($alertId)?->delete();
    }

    public function render(MatchingService $matching): View
    {
        $alerts = auth()->user()
            ->alerts()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();

        $counts = [];
        foreach ($alerts as $alert) {
            $counts[$alert->id] = $alert->is_active ? $matching->countMatches($alert) : 0;
        }

        return view('livewire.alerts.alerts-list', [
            'alerts' => $alerts,
            'matchCounts' => $counts,
            'frequencyOptions' => AlertFrequency::options(),
        ])->title('Mes alertes | AutoAlert');
    }
}
