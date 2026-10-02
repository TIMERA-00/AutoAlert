<?php

namespace App\Http\Controllers;

use App\Enums\NotificationStatus;
use App\Models\Alert;
use App\Models\Favorite;
use App\Models\Notification;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $counts = [
            'favorites' => Favorite::where('user_id', $user->id)->count(),
            'alerts' => Alert::where('user_id', $user->id)->count(),
            'activeAlerts' => Alert::where('user_id', $user->id)->where('is_active', true)->count(),
            'matches' => Notification::where('user_id', $user->id)->count(),
            'newMatches' => Notification::where('user_id', $user->id)->whereNull('read_at')->count(),
        ];

        $recentMatches = Notification::with(['vehicle.images', 'alert'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(6)
            ->get();

        $favorites = Favorite::with('vehicle.images')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(4)
            ->get();

        $suggestions = Vehicle::published()
            ->when(
                $user->alerts()->active()->exists(),
                fn ($q) => $q->whereNotIn('brand', Alert::where('user_id', $user->id)->whereNotNull('brand')->pluck('brand')),
                fn ($q) => $q
            )
            ->with('images')
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        return view('account.dashboard', [
            'user' => $user,
            'counts' => $counts,
            'recentMatches' => $recentMatches,
            'favorites' => $favorites,
            'suggestions' => $suggestions,
            'failedNotifications' => Notification::where('user_id', $user->id)
                ->where('status', NotificationStatus::Failed)
                ->count(),
        ]);
    }
}
