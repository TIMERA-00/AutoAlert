<x-layouts.admin>
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-ink-900">Dashboard</h1>
        <p class="mt-1 text-sm text-ink-500">Vue d ensemble de l activite de la plateforme.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Vehicules" :value="number_format($stats['vehicles']['total'])" tone="brand"
                     :hint="$stats['vehicles']['published'].' publie(s) · '.$stats['vehicles']['published_this_month'].' ce mois'" />
        <x-stat-card label="Utilisateurs" :value="number_format($stats['users']['total'])"
                     :hint="$stats['users']['new_this_week'].' inscription(s) cette semaine'" />
        <x-stat-card label="Alertes" :value="number_format($stats['alerts']['total'])"
                     :hint="$stats['alerts']['active'].' active(s)'" />
        <x-stat-card label="Notifications" :value="number_format($stats['notifications']['total'])"
                     :hint="$stats['notifications']['failed'].' echec(s)'"
                     :tone="$stats['notifications']['failed'] > 0 ? 'danger' : 'success'" />
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Disponibles', $stats['vehicles']['published'], 'success'],
            ['Reserves', $stats['vehicles']['reserved'], 'warning'],
            ['Vendus', $stats['vehicles']['sold'], 'danger'],
            ['Brouillons', $stats['vehicles']['draft'], 'default'],
        ] as [$label, $value, $tone])
            <x-stat-card :label="$label" :value="number_format($value)" :tone="$tone" />
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <div class="card p-6">
            <h2 class="font-semibold text-ink-900">Activite des 14 derniers jours</h2>
            <p class="mt-1 text-sm text-ink-500">Publications, visites et inscriptions par jour.</p>

            <div class="mt-6 space-y-4">
                @php
                    $maxPublished = max(1, collect($timeline)->max('published'));
                    $maxViews = max(1, collect($timeline)->max('views'));
                @endphp

                @foreach ($timeline as $day)
                    <div wire:key="day-{{ $day['day'] }}">
                        <div class="flex items-center justify-between text-xs text-ink-500">
                            <span>{{ \Illuminate\Support\Carbon::parse($day['day'])->translatedFormat('D j M') }}</span>
                            <span>{{ $day['published'] }} publie(s) · {{ $day['views'] }} visite(s) · {{ $day['signups'] }} inscription(s)</span>
                        </div>
                        <div class="mt-1.5 flex gap-1">
                            <span class="h-2 rounded-full bg-brand-500"
                                  style="width: {{ max(2, round($day['published'] / $maxPublished * 60)) }}%"></span>
                            <span class="h-2 rounded-full bg-ink-300"
                                  style="width: {{ max(2, round($day['views'] / $maxViews * 40)) }}%"></span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 flex gap-4 text-xs text-ink-500">
                <span class="flex items-center gap-1.5"><span class="h-2 w-4 rounded-full bg-brand-500"></span> Publications</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-4 rounded-full bg-ink-300"></span> Visites</span>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-5">
                <h2 class="font-semibold text-ink-900">Vehicules les plus consultes</h2>
                <ul class="mt-4 space-y-3">
                    @forelse ($topVehicles as $top)
                        <li wire:key="top-{{ $top['id'] }}" class="flex items-center justify-between gap-3 text-sm">
                            <a href="{{ route('vehicles.show', $top['id']) }}" class="truncate text-ink-800 hover:text-brand-700">
                                {{ $top['brand'] }} {{ $top['model'] }} {{ $top['year'] }}
                            </a>
                            <span class="shrink-0 text-xs text-ink-500">{{ $top['view_count'] }} vues</span>
                        </li>
                    @empty
                        <li class="text-sm text-ink-500">Aucune donnee.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card p-5">
                <h2 class="font-semibold text-ink-900">Recherches populaires</h2>
                <ul class="mt-4 space-y-3">
                    @forelse ($topSearches as $search)
                        <li wire:key="search-{{ $search['term'] }}" class="flex items-center justify-between gap-3 text-sm">
                            <span class="truncate text-ink-800">{{ $search['term'] }}</span>
                            <span class="shrink-0 text-xs text-ink-500">{{ $search['count'] }}x</span>
                        </li>
                    @empty
                        <li class="text-sm text-ink-500">Aucune recherche enregistree.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card p-5">
                <h2 class="font-semibold text-ink-900">Repartition par marque</h2>
                <ul class="mt-4 space-y-2">
                    @foreach ($brands as $brand)
                        <li wire:key="brand-{{ $brand['brand'] }}" class="flex items-center justify-between text-sm">
                            <span class="text-ink-800">{{ $brand['brand'] }}</span>
                            <span class="text-xs text-ink-500">{{ $brand['total'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-layouts.admin>