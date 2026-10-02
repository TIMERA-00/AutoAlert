<?php

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Enums\VehicleStatus;
use App\Models\Alert;
use App\Models\Favorite;
use App\Models\Notification;
use App\Models\PageView;
use App\Models\SearchQuery;
use App\Models\Source;
use App\Models\SourceClick;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;

class StatisticsService
{
    public function overview(): array
    {
        $since30 = now()->subDays(30);
        $since7 = now()->subDays(7);

        return [
            'vehicles' => [
                'total' => Vehicle::count(),
                'published' => Vehicle::where('status', VehicleStatus::Published)->count(),
                'reserved' => Vehicle::where('status', VehicleStatus::Reserved)->count(),
                'sold' => Vehicle::where('status', VehicleStatus::Sold)->count(),
                'draft' => Vehicle::where('status', VehicleStatus::Draft)->count(),
                'archived' => Vehicle::where('status', VehicleStatus::Archived)->count(),
                'published_this_month' => Vehicle::where('status', VehicleStatus::Published)
                    ->where('published_at', '>=', now()->startOfMonth())->count(),
                'average_price' => (int) round((float) Vehicle::where('status', VehicleStatus::Published)->avg('price')),
                'total_views' => (int) Vehicle::sum('view_count'),
            ],
            'users' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'admins' => User::where('role', 'ADMIN')->count(),
                'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
                'new_this_week' => User::where('created_at', '>=', $since7)->count(),
            ],
            'alerts' => [
                'total' => Alert::count(),
                'active' => Alert::where('is_active', true)->count(),
                'created_this_month' => Alert::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'notifications' => [
                'total' => Notification::count(),
                'sent' => Notification::where('status', NotificationStatus::Sent)->count(),
                'failed' => Notification::where('status', NotificationStatus::Failed)->count(),
                'sent_this_month' => Notification::where('sent_at', '>=', now()->startOfMonth())->count(),
            ],
            'engagement' => [
                'favorites' => Favorite::count(),
                'favorites_this_week' => Favorite::where('created_at', '>=', $since7)->count(),
                'page_views' => PageView::count(),
                'page_views_today' => PageView::where('created_at', '>=', now()->startOfDay())->count(),
                'page_views_this_week' => PageView::where('created_at', '>=', $since7)->count(),
                'unique_visitors_today' => PageView::where('created_at', '>=', now()->startOfDay())->distinct('session_id')->count('session_id'),
                'searches' => SearchQuery::count(),
                'searches_today' => SearchQuery::where('created_at', '>=', now()->startOfDay())->count(),
                'source_clicks' => SourceClick::count(),
                'source_clicks_this_week' => SourceClick::where('created_at', '>=', $since7)->count(),
            ],
        ];
    }

    public function topVehicles(int $limit = 10): array
    {
        return Vehicle::query()
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get(['id', 'brand', 'model', 'year', 'price', 'view_count', 'favorite_count', 'status'])
            ->toArray();
    }

    public function topSearches(int $limit = 10): array
    {
        return SearchQuery::query()
            ->whereNotNull('term')
            ->where('term', '!=', '*')
            ->get()
            ->groupBy(fn (SearchQuery $search) => mb_strtolower(trim($search->term)))
            ->map(fn ($group) => [
                'term' => $group->first()->term,
                'count' => $group->count(),
                'results' => (int) round($group->avg('results')),
            ])
            ->sortByDesc('count')
            ->take($limit)
            ->values()
            ->toArray();
    }

    public function sourcePerformance(): array
    {
        return Source::query()
            ->withCount('vehicles')
            ->get()
            ->map(function (Source $source) {
                $vehicleIds = $source->vehicles()->pluck('id');

                return [
                    'id' => $source->id,
                    'name' => $source->name,
                    'host' => $source->host(),
                    'type' => $source->type->value,
                    'is_active' => $source->is_active,
                    'vehicles' => $vehicleIds->count(),
                    'views' => (int) Vehicle::whereIn('id', $vehicleIds)->sum('view_count'),
                    'clicks' => $vehicleIds->isEmpty() ? 0 : SourceClick::whereIn('vehicle_id', $vehicleIds)->count(),
                ];
            })
            ->sortByDesc('vehicles')
            ->toArray();
    }

    /** Daily series of published vehicles and visits for the admin chart. */
    public function timeline(int $days = 14): array
    {
        $days = max(1, min($days, 90));
        $start = now()->subDays($days - 1)->startOfDay();

        $published = Vehicle::query()
            ->where('published_at', '>=', $start)
            ->selectRaw('DATE(published_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $views = PageView::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $signups = User::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = Carbon::parse($start)->addDays($i)->format('Y-m-d');
            $series[] = [
                'day' => $day,
                'published' => (int) ($published[$day] ?? 0),
                'views' => (int) ($views[$day] ?? 0),
                'signups' => (int) ($signups[$day] ?? 0),
            ];
        }

        return $series;
    }

    public function distributionByBrand(int $limit = 8): array
    {
        return Vehicle::query()
            ->select('brand')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['brand' => $row->brand, 'total' => (int) $row->total])
            ->toArray();
    }

    /** Public dashboard numbers (no sensitive data). */
    public function publicStats(): array
    {
        return [
            'vehicles' => Vehicle::where('status', VehicleStatus::Published)->count(),
            'brands' => Vehicle::where('status', VehicleStatus::Published)->distinct()->count('brand'),
            'alerts' => Alert::where('is_active', true)->count(),
            'updates_this_week' => Vehicle::where('status', VehicleStatus::Published)
                ->where('published_at', '>=', now()->subDays(7))->count(),
        ];
    }
}
