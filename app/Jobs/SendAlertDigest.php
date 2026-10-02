<?php

namespace App\Jobs;

use App\Enums\AlertFrequency;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Alert;
use App\Models\Notification;
use App\Models\Vehicle;
use App\Notifications\AlertDigestNotification;
use App\Services\MatchingService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/** Daily / weekly digest: one email per user summarising every match of the period. */
class SendAlertDigest implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public AlertFrequency $frequency) {}

    public function handle(MatchingService $matching, WhatsAppService $whatsApp): void
    {
        $periodHours = $this->frequency === AlertFrequency::Daily ? 24 : 24 * 7;
        $since = now()->subHours($periodHours);

        $recent = Vehicle::query()
            ->published()
            ->where('published_at', '>=', $since)
            ->orderByDesc('published_at')
            ->limit(300)
            ->get();

        if ($recent->isEmpty()) {
            return;
        }

        $alerts = Alert::query()
            ->active()
            ->where('frequency', $this->frequency)
            ->with('user')
            ->get()
            ->groupBy('user_id');

        $sent = 0;

        foreach ($alerts as $userAlerts) {
            $user = $userAlerts->first()->user;

            if (! $user || ! $user->is_active || ! $user->notify_email) {
                continue;
            }

            /** @var array<string, array{alert: Alert, vehicles: Collection}> $buckets */
            $buckets = [];

            foreach ($userAlerts as $alert) {
                $matches = $recent->filter(fn (Vehicle $v) => $matching->matches($alert, $v));
                if ($matches->isEmpty()) {
                    continue;
                }
                $buckets[] = ['alert' => $alert, 'vehicles' => $matches->take(10)->values()];
            }

            if ($buckets === []) {
                continue;
            }

            $user->notify(new AlertDigestNotification($buckets, $this->frequency));

            foreach ($buckets as $bucket) {
                foreach ($bucket['vehicles'] as $vehicle) {
                    $exists = Notification::query()->where([
                        ['user_id', '=', $user->id],
                        ['vehicle_id', '=', $vehicle->id],
                        ['alert_id', '=', $bucket['alert']->id],
                        ['channel', '=', NotificationChannel::Email],
                    ])->exists();

                    if ($exists) {
                        continue;
                    }

                    Notification::create([
                        'user_id' => $user->id,
                        'vehicle_id' => $vehicle->id,
                        'alert_id' => $bucket['alert']->id,
                        'channel' => NotificationChannel::Email,
                        'status' => NotificationStatus::Sent,
                        'sent_at' => now(),
                        'subject' => 'Resume '.($this->frequency === AlertFrequency::Daily ? 'quotidien' : 'hebdomadaire')." - {$bucket['alert']->name}",
                    ]);
                }
            }

            $userAlerts->each(fn (Alert $a) => $a->forceFill(['last_notified_at' => now()])->save());
            $sent++;
        }

        Log::info('Alert digest processed', ['frequency' => $this->frequency->value, 'users' => $sent]);
    }
}
