<?php

namespace App\Jobs;

use App\Models\Vehicle;
use App\Services\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

/** Finds every alert matching a newly published vehicle and notifies the users. */
class MatchAlertsForVehicle implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $vehicleId) {}

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("match-vehicle-{$this->vehicleId}"))->expireAfter(300)];
    }

    public function handle(NotificationDispatcher $dispatcher): void
    {
        $vehicle = Vehicle::with('images')->find($this->vehicleId);

        if (! $vehicle || ! $vehicle->isPublished()) {
            Log::info('MatchAlertsForVehicle: ignored, vehicle not published', ['id' => $this->vehicleId]);

            return;
        }

        $count = $dispatcher->dispatchForVehicle($vehicle);
        Log::info('MatchAlertsForVehicle completed', ['vehicle' => $this->vehicleId, 'notifications' => $count]);
    }
}
