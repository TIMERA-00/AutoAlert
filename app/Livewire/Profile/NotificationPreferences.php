<?php

namespace App\Livewire\Profile;

use App\Enums\AlertFrequency;
use App\Enums\NotificationChannel;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class NotificationPreferences extends Component
{
    public bool $notifyEmail = true;

    public bool $notifyWhatsapp = false;

    public bool $notifyInApp = true;

    public string $defaultFrequency = 'IMMEDIATE';

    public function mount(): void
    {
        $user = auth()->user();

        $this->fill([
            'notifyEmail' => $user->notify_email,
            'notifyWhatsapp' => $user->notify_whatsapp,
            'notifyInApp' => $user->notify_in_app,
            'defaultFrequency' => $user->default_frequency->value,
        ]);
    }

    public function save(): void
    {
        $this->validate([
            'notifyEmail' => ['boolean'],
            'notifyWhatsapp' => ['boolean'],
            'notifyInApp' => ['boolean'],
            'defaultFrequency' => ['required', Rule::in(array_keys(AlertFrequency::options()))],
        ]);

        if ($this->notifyWhatsapp && blank(auth()->user()->phone)) {
            $this->addError('notifyWhatsapp', 'Renseignez votre numero de telephone dans votre profil pour activer WhatsApp.');

            return;
        }

        auth()->user()->update([
            'notify_email' => $this->notifyEmail,
            'notify_whatsapp' => $this->notifyWhatsapp,
            'notify_in_app' => $this->notifyInApp,
            'default_frequency' => $this->defaultFrequency,
        ]);

        $this->dispatch('preferences-saved', message: 'Preferences de notification mises a jour.');
    }

    public function render(): View
    {
        return view('livewire.profile.notification-preferences', [
            'frequencyOptions' => AlertFrequency::options(),
            'channelOptions' => NotificationChannel::options(),
            'user' => auth()->user(),
        ])->title('Preferences de notification | AutoAlert');
    }
}
