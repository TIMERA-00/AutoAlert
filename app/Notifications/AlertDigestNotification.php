<?php

namespace App\Notifications;

use App\Enums\AlertFrequency;
use App\Models\Alert;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Collection;

class AlertDigestNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array{alert: Alert, vehicles: Collection<int, Vehicle>}>  $buckets
     */
    public function __construct(
        public array $buckets,
        public AlertFrequency $frequency,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = collect($this->buckets)->sum(fn (array $bucket) => $bucket['vehicles']->count());
        $label = $this->frequency === AlertFrequency::Daily ? 'quotidienne' : 'hebdomadaire';

        $message = (new MailMessage)
            ->subject("{$total} vehicule(s) correspondent a vos alertes - resume {$label}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Voici votre synthese {$label} : {$total} vehicule(s) correspondent a vos alertes.");

        foreach ($this->buckets as $bucket) {
            /** @var Alert $alert */
            $alert = $bucket['alert'];
            $message->line("{$alert->name} ({$alert->summary()})");

            foreach ($bucket['vehicles']->take(5) as $vehicle) {
                $message->line("• {$vehicle->brand} {$vehicle->model} {$vehicle->year} — ".number_format($vehicle->price, 0, ',', ' ').' FCFA');
            }
        }

        return $message
            ->action('Voir le catalogue', route('vehicles.index'))
            ->line('Desactivez une alerte pour ne plus recevoir son resume.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['total' => collect($this->buckets)->sum(fn (array $b) => $b['vehicles']->count())];
    }
}
