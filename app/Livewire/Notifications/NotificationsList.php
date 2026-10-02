<?php

namespace App\Livewire\Notifications;

use App\Enums\NotificationChannel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class NotificationsList extends Component
{
    use WithPagination;

    public bool $unreadOnly = false;

    public string $channel = '';

    public function markRead(int $id): void
    {
        $notification = auth()->user()->notifications()->find($id);

        if ($notification && ! $notification->read_at) {
            $notification->update(['read_at' => now(), 'status' => 'READ']);
        }
    }

    public function markAllRead(): void
    {
        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now(), 'status' => 'READ']);
    }

    public function delete(int $id): void
    {
        auth()->user()->notifications()->find($id)?->delete();
    }

    public function render(): View
    {
        $query = auth()->user()
            ->notifications()
            ->with(['vehicle.images', 'alert'])
            ->when($this->unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->when($this->channel !== '', fn ($q) => $q->where('channel', $this->channel))
            ->orderByDesc('created_at');

        return view('livewire.notifications.notifications-list', [
            'notifications' => $query->paginate(15),
            'unreadCount' => auth()->user()->notifications()->whereNull('read_at')->count(),
            'channelOptions' => NotificationChannel::options(),
        ])->title('Mes notifications | AutoAlert');
    }
}
