<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function __construct() {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('verification.verify', [
            'id' => $notifiable->getKey(),
            'hash' => sha1((string) $notifiable->getEmailForVerification()),
        ]);

        return (new MailMessage)
            ->subject('Confirmez votre adresse email - AutoAlert')
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('Confirmez votre adresse email pour activer vos alertes et vos notifications.')
            ->action('Confirmer mon email', $url)
            ->line('Si vous n avez pas cree de compte AutoAlert, ignorez cet email.');
    }
}
