<x-layouts.admin>
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-ink-900">Statistiques</h1>
        <p class="mt-1 text-sm text-ink-500">Trafic, engagement et performance des sources sur 30 jours.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Visiteurs (30 j)" :value="number_format($stats['engagement']['page_views'])"
                     :hint="$stats['engagement']['unique_visitors_today'].' aujourd hui'" />
        <x-stat-card label="Recherches" :value="number_format($stats['engagement']['searches'])"
                     :hint="$stats['engagement']['searches_today'].' aujourd hui'" tone="brand" />
        <x-stat-card label="Favoris crees" :value="number_format($stats['engagement']['favorites'])"
                     :hint="$stats['engagement']['favorites_this_week'].' cette semaine'" tone="success" />
        <x-stat-card label="Clics vers les sources" :value="number_format($stats['engagement']['source_clicks'])"
                     :hint="$stats['engagement']['source_clicks_this_week'].' cette semaine'" tone="warning" />
    </div>

    <div class="mt-6 card p-6">
        <h2 class="font-semibold text-ink-900">Publications et visites (30 jours)</h2>
        <div class="mt-6 space-y-3">
            @php $maxPublished = max(1, collect($timeline)->max('published')); $maxViews = max(1, collect($timeline)->max('views')); @endphp
            @foreach ($timeline as $day)
                <div wire:key="stat-{{ $day['day'] }}" class="flex items-center gap-3 text-xs">
                    <span class="w-20 shrink-0 text-ink-500">{{ \Illuminate\Support\Carbon::parse($day['day'])->format('d/m') }}</span>
                    <span class="h-2 w-full max-w-[420px] rounded-full bg-ink-100">
                        <span class="block h-2 rounded-full bg-brand-500"
                              style="width: {{ max(2, round($day['published'] / $maxPublished * 100)) }}%"></span>
                    </span>
                    <span class="w-10 text-right text-ink-700">{{ $day['published'] }}</span>
                    <span class="h-2 w-full max-w-[420px] rounded-full bg-ink-100">
                        <span class="block h-2 rounded-full bg-ink-400"
                              style="width: {{ max(2, round($day['views'] / $maxViews * 100)) }}%"></span>
                    </span>
                    <span class="w-10 text-right text-ink-700">{{ $day['views'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-4 flex gap-4 text-xs text-ink-500">
            <span class="flex items-center gap-1.5"><span class="h-2 w-4 rounded-full bg-brand-500"></span> Vehicules publies</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-4 rounded-full bg-ink-400"></span> Visites</span>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card p-6">
            <h2 class="font-semibold text-ink-900">Vehicules les plus consultes</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-ink-500">
                        <tr>
                            <th class="py-2 font-semibold">Vehicule</th>
                            <th class="py-2 font-semibold">Prix</th>
                            <th class="py-2 font-semibold">Vues</th>
                            <th class="py-2 font-semibold">Favoris</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($topVehicles as $vehicle)
                            <tr wire:key="tv-{{ $vehicle['id'] }}">
                                <td class="py-2">
                                    <a href="{{ route('vehicles.show', $vehicle['id']) }}" class="text-ink-800 hover:text-brand-700">
                                        {{ $vehicle['brand'] }} {{ $vehicle['model'] }} {{ $vehicle['year'] }}
                                    </a>
                                </td>
                                <td class="py-2 text-ink-600">{{ number_format($vehicle['price'], 0, ',', ' ') }}</td>
                                <td class="py-2 text-ink-900">{{ $vehicle['view_count'] }}</td>
                                <td class="py-2 text-ink-900">{{ $vehicle['favorite_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="font-semibold text-ink-900">Performance des sources</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-ink-500">
                        <tr>
                            <th class="py-2 font-semibold">Source</th>
                            <th class="py-2 font-semibold">Annonces</th>
                            <th class="py-2 font-semibold">Vues</th>
                            <th class="py-2 font-semibold">Clics</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($sources as $source)
                            <tr wire:key="src-{{ $source['id'] }}">
                                <td class="py-2">
                                    <span class="block text-ink-800">{{ $source['name'] }}</span>
                                    <span class="block text-xs text-ink-500">{{ $source['host'] }} · {{ $source['type'] }}</span>
                                </td>
                                <td class="py-2 text-ink-900">{{ $source['vehicles'] }}</td>
                                <td class="py-2 text-ink-900">{{ $source['views'] }}</td>
                                <td class="py-2 text-ink-900">{{ $source['clicks'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-ink-500">Aucune source enregistree.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-6 card p-6">
        <h2 class="font-semibold text-ink-900">Recherches populaires</h2>
        <div class="mt-4 flex flex-wrap gap-2">
            @forelse ($topSearches as $search)
                <span class="badge border-ink-200 bg-ink-50 text-ink-700">
                    {{ $search['term'] }} <span class="text-ink-400">{{ $search['count'] }}x</span>
                </span>
            @empty
                <span class="text-sm text-ink-500">Aucune recherche enregistree.</span>
            @endforelse
        </div>
    </div>
</x-layouts.admin>