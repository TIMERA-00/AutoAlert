<?php

namespace App\Services;

use App\Enums\AlertFrequency;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotification;
use App\Models\Alert;
use App\Models\Notification;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    public function __construct(private readonly MatchingService $matching) {}

    /**
     * Called right after a vehicle becomes PUBLISHED.
     * Creates one notification per (user, vehicle, alert, channel) and queues delivery.
     */
    public function dispatchForVehicle(Vehicle $vehicle): int
    {
        $created = 0;

        foreach ($this->matching->matchingAlertsFor($vehicle) as $match) {
            /** @var Alert $alert */
            $alert = $match['alert'];
            $user = $alert->user;

            if (! $user || ! $user->is_active) {
                continue;
            }

            // Digest alerts are handled by the scheduled digest job, not per vehicle.
            if ($alert->frequency !== AlertFrequency::Immediate) {
                continue;
            }

            foreach ($user->enabledChannels() as $channel) {
                if ($this->record($user->id, $vehicle->id, $alert->id, $channel)) {
                    $created++;
                }
            }

            $alert->forceFill(['last_notified_at' => now()])->save();
        }

        if ($created > 0) {
            Log::info('Vehicle published: notifications created', [
                'vehicle_id' => $vehicle->id,
                'notifications' => $created,
            ]);
        }

        return $created;
    }

    /** Creates the notification row (idempotent) and queues the delivery job. */
    public function record(int $userId, int $vehicleId, ?int $alertId, NotificationChannel $channel): bool
    {
        $existing = Notification::query()
            ->where('user_id', $userId)
            ->where('vehicle_id', $vehicleId)
            ->where('alert_id', $alertId)
            ->where('channel', $channel)
            ->exists();

        if ($existing) {
            return false;
        }

        $notification = Notification::create([
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
            'alert_id' => $alertId,
            'channel' => $channel,
            'status' => $channel === NotificationChannel::InApp
                ? NotificationStatus::Sent
                : NotificationStatus::Queued,
            'sent_at' => $channel === NotificationChannel::InApp ? now() : null,
            'queued_at' => now(),
        ]);

        if ($channel !== NotificationChannel::InApp) {
            dispatch(new SendNotification($notification->id));
        }

        return true;
    }

    /** In-app notification used for the user dashboard badge. */
    public function notifyUser(int $userId, Vehicle $vehicle, ?Alert $alert = null, ?string $subject = null): ?Notification
    {
        $before = Notification::query()
            ->where('user_id', $userId)
            ->where('vehicle_id', $vehicle->id)
            ->where('alert_id', $alert?->id)
            ->where('channel', NotificationChannel::InApp)
            ->exists();

        if ($before) {
            return null;
        }

        return Notification::create([
            'user_id' => $userId,
            'vehicle_id' => $vehicle->id,
            'alert_id' => $alert?->id,
            'channel' => NotificationChannel::InApp,
            'status' => NotificationStatus::Sent,
            'subject' => $subject ?? "Nouvelle voiture correspondant a votre alerte : {$vehicle->title()}",
            'sent_at' => now(),
        ]);
    }
}
