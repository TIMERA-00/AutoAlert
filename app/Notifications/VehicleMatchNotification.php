<?php

namespace App\Notifications;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class VehicleMatchNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Notification $notification) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vehicle = $this->notification->vehicle;
        $alert = $this->notification->alert;

        $message = (new MailMessage)
            ->subject("Nouvelle voiture correspondant a votre alerte : {$vehicle->title()}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line($alert
                ? "Une nouvelle voiture correspond a votre alerte « {$alert->name} »."
                : 'Une nouvelle voiture vient d etre publiee sur AutoAlert.')
            ->line("{$vehicle->brand} {$vehicle->model} {$vehicle->year}")
            ->line('Prix : '.number_format($vehicle->price, 0, ',', ' ').' FCFA')
            ->line('Kilometrage : '.number_format($vehicle->mileage, 0, ',', ' ').' km')
            ->line('Boite : '.($vehicle->transmission?->label() ?? '-'))
            ->line('Carburant : '.($vehicle->fuel?->label() ?? '-'))
            ->action('Voir le vehicule', route('vehicles.show', $vehicle))
            ->line('Ce vehicule vient d etre publie : les premiers arrivent en premier.');

        return $message;
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'notification_id' => $this->notification->id,
            'vehicle_id' => $this->notification->vehicle_id,
            'url' => route('vehicles.show', $this->notification->vehicle),
        ];
    }
}
