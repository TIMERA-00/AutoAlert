<?php

namespace App\Jobs;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Notifications\VehicleMatchNotification;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Delivers a single notification over its channel (email, WhatsApp or in-app). */
class SendNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(public int $notificationId) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        $notification = Notification::with(['user', 'vehicle.images', 'alert'])->find($this->notificationId);

        if (! $notification) {
            return;
        }
        if ($notification->status === NotificationStatus::Sent) {
            return;
        }

        $user = $notification->user;
        $vehicle = $notification->vehicle;

        if (! $user || ! $user->is_active) {
            $notification->update(['status' => NotificationStatus::Failed, 'error' => 'Compte inactif']);

            return;
        }

        try {
            match ($notification->channel) {
                NotificationChannel::Email => $user->notify(new VehicleMatchNotification($notification)),
                NotificationChannel::Whatsapp => $this->sendWhatsApp($notification, $whatsApp),
                default => null,
            };

            $notification->update([
                'status' => NotificationStatus::Sent,
                'sent_at' => now(),
                'error' => null,
                'attempts' => $notification->attempts + 1,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Notification delivery failed', [
                'notification' => $notification->id,
                'error' => $e->getMessage(),
            ]);
            $notification->update([
                'status' => NotificationStatus::Failed,
                'error' => $e->getMessage(),
                'attempts' => $notification->attempts + 1,
            ]);

            throw $e;
        }
    }

    private function sendWhatsApp(Notification $notification, WhatsAppService $whatsApp): void
    {
        if (! $notification->user->phone) {
            throw new \RuntimeException('Aucun numero de telephone enregistre');
        }

        $result = $whatsApp->send(
            $notification->user->phone,
            $this->buildWhatsAppBody($notification),
        );

        if (! $result['accepted']) {
            throw new \RuntimeException($result['error'] ?? 'Envoi WhatsApp impossible');
        }
    }

    private function buildWhatsAppBody(Notification $notification): string
    {
        $vehicle = $notification->vehicle;
        $url = route('vehicles.show', $vehicle);

        return implode("\n", [
            'AutoAlert 🚗',
            'Nouvelle voiture correspondant a votre alerte'.($notification->alert ? " « {$notification->alert->name} »" : '').'.',
            '',
            "*{$vehicle->brand} {$vehicle->model} {$vehicle->year}*",
            'Prix: '.number_format($vehicle->price, 0, ',', ' ').' FCFA',
            'Kilometrage: '.number_format($vehicle->mileage, 0, ',', ' ').' km',
            'Boite: '.($vehicle->transmission?->label() ?? '-'),
            'Carburant: '.($vehicle->fuel?->label() ?? '-'),
            '',
            $url,
        ]);
    }
}
