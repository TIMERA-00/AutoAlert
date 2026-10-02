<?php

namespace App\Livewire\Admin;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotification;
use App\Models\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class NotificationLog extends Component
{
    use WithPagination;

    #[Url(as: 'canal', except: '')]
    public string $channel = '';

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    public function retry(int $id): void
    {
        $notification = Notification::findOrFail($id);

        if ($notification->channel === NotificationChannel::InApp) {
            return;
        }

        $notification->update(['status' => NotificationStatus::Queued, 'error' => null]);
        dispatch(new SendNotification($notification->id));

        $this->dispatch('toast', message: 'Notification remise en file.', type: 'success');
    }

    public function render(): View
    {
        $notifications = Notification::query()
            ->with(['user', 'vehicle', 'alert'])
            ->when($this->channel !== '', fn ($q) => $q->where('channel', $this->channel))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('created_at')
            ->paginate(25);

        $summary = Notification::query()
            ->selectRaw('channel, status, COUNT(*) as total')
            ->groupBy('channel', 'status')
            ->get();

        return view('livewire.admin.notification-log', [
            'notifications' => $notifications,
            'summary' => $summary,
            'channelOptions' => NotificationChannel::options(),
            'statusOptions' => collect(NotificationStatus::cases())->mapWithKeys(
                fn (NotificationStatus $status) => [$status->value => $status->label()]
            )->all(),
        ])->title('Administration - notifications | AutoAlert');
    }
}
