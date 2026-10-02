<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class UserManager extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'role', except: '')]
    public string $role = '';

    public bool $showDetail = false;

    public ?int $detailId = null;

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->dispatch('toast', message: 'Vous ne pouvez pas desactiver votre propre compte.', type: 'error');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
        $this->dispatch('toast', message: 'Statut du compte mis a jour.', type: 'success');
    }

    public function makeAdmin(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            return;
        }

        $user->update(['role' => $user->isAdmin() ? UserRole::User : UserRole::Admin]);
    }

    public function showDetail(int $userId): void
    {
        $this->detailId = $userId;
        $this->showDetail = true;
    }

    public function render(): View
    {
        $users = User::query()
            ->search($this->search)
            ->when($this->role !== '', fn ($q) => $q->where('role', $this->role))
            ->withCount(['alerts', 'favorites'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $detail = $this->detailId
            ? User::with([
                'alerts' => fn ($q) => $q->latest()->limit(10),
                'notifications' => fn ($q) => $q->latest()->limit(10)->with('vehicle'),
            ])->find($this->detailId)
            : null;

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'detail' => $detail,
            'roleOptions' => UserRole::cases(),
        ])->title('Administration - utilisateurs | AutoAlert');
    }
}
