<?php

use App\Enums\AlertFrequency;
use App\Jobs\SendAlertDigest;
use App\Models\PageView;
use App\Models\SourceClick;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
|
| `php artisan schedule:work` (dev) or the cron entry below (production) is
| required, otherwise the digest jobs and the housekeeping never run.
|
*/

// Digests run at a local hour that makes sense for the market they target.
Schedule::job(new SendAlertDigest(AlertFrequency::Daily))
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new SendAlertDigest(AlertFrequency::Weekly))
    ->weeklyOn(1, '08:15')
    ->withoutOverlapping()
    ->onOneServer();

// Housekeeping: page views and source clicks older than 18 months are noise.
Schedule::call(function (): void {
    PageView::where('created_at', '<', now()->subMonths(18))->delete();
    SourceClick::where('created_at', '<', now()->subMonths(18))->delete();
})->dailyAt('03:30')->name('purge-old-tracking')->withoutOverlapping();
