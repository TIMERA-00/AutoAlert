<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject('Reinitialisation de votre mot de passe AutoAlert')
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line('Vous avez demande la reinitialisation de votre mot de passe AutoAlert.')
            ->action('Choisir un nouveau mot de passe', $url)
            ->line('Ce lien est valable 60 minutes. Si vous n etes pas a l origine de cette demande, ignorez cet email.');
    }
}
