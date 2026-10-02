<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Contracts\View\View;

class AdminStatsController extends Controller
{
    public function __invoke(StatisticsService $stats): View
    {
        return view('admin.stats', [
            'stats' => $stats->overview(),
            'timeline' => $stats->timeline(30),
            'topVehicles' => $stats->topVehicles(15),
            'topSearches' => $stats->topSearches(15),
            'sources' => $stats->sourcePerformance(),
            'brands' => $stats->distributionByBrand(10),
        ]);
    }
}
