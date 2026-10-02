<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(StatisticsService $stats): View
    {
        $overview = $stats->overview();

        return view('admin.dashboard', [
            'stats' => $overview,
            'timeline' => $stats->timeline(14),
            'topVehicles' => $stats->topVehicles(5),
            'topSearches' => $stats->topSearches(6),
            'brands' => $stats->distributionByBrand(6),
        ]);
    }
}
