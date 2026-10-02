<?php

namespace App\Livewire\Admin;

use App\Enums\AlertFrequency;
use App\Models\Alert;
use App\Services\MatchingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class AlertMonitor extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'freq', except: '')]
    public string $frequency = '';

    public function toggle(int $alertId): void
    {
        $alert = Alert::findOrFail($alertId);
        $alert->update(['is_active' => ! $alert->is_active]);
    }

    public function delete(int $alertId): void
    {
        Alert::findOrFail($alertId)->delete();
    }

    public function render(MatchingService $matching): View
    {
        $alerts = Alert::query()
            ->with('user')
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$this->search}%")))
            ->when($this->frequency !== '', fn ($q) => $q->where('frequency', $this->frequency))
            ->withCount('notifications')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.alert-monitor', [
            'alerts' => $alerts,
            'frequencyOptions' => AlertFrequency::options(),
            'total' => Alert::count(),
            'active' => Alert::where('is_active', true)->count(),
        ])->title('Administration - alertes | AutoAlert');
    }
}
