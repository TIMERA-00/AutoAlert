<x-layouts.app>
    <div class="container-app py-10">
        <header class="mb-8">
            <h1 class="text-2xl font-bold tracking-tight text-ink-900">Bonjour {{ $user->first_name }} 👋</h1>
            <p class="mt-1 text-sm text-ink-500">Voici l actualite de vos recherches.</p>
        </header>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Mes favoris" :value="$counts['favorites']" />
            <x-stat-card label="Mes alertes" :value="$counts['alerts']" :hint="$counts['activeAlerts'].' active(s)'" />
            <x-stat-card label="Nouvelles correspondances" :value="$counts['newMatches']" tone="brand"
                         hint="Depuis votre derniere visite" />
            <x-stat-card label="Total des correspondances" :value="$counts['matches']" />
        </div>

        @if ($failedNotifications > 0)
            <div class="alert-box mt-6 border-amber-200 bg-amber-50 text-amber-800">
                {{ $failedNotifications }} notification(s) n ont pas pu etre envoyees.
                <a href="{{ route('preferences.edit') }}" class="font-medium underline">Verifier mes preferences</a>
            </div>
        @endif

        <section class="mt-10">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-ink-900">Dernieres correspondances</h2>
                    <p class="mt-1 text-sm text-ink-500">Les vehicules qui ont declenche vos alertes.</p>
                </div>
                <a href="{{ route('notifications.index') }}" class="btn-secondary btn-sm">Toutes les notifications</a>
            </div>

            @if ($recentMatches->isEmpty())
                <div class="mt-5">
                    <x-empty-state title="Aucune correspondance pour le moment"
                                   description="Ajustez vos alertes ou elargissez vos criteres pour recevoir plus vite.">
                        <a href="{{ route('alerts.create') }}" class="btn-primary mt-2">Creer une alerte</a>
                    </x-empty-state>
                </div>
            @else
                <div class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($recentMatches as $match)
                        <div wire:key="match-{{ $match->id }}" class="relative">
                            <x-vehicle-card :vehicle="$match->vehicle" />
                            @unless ($match->read_at)
                                <span class="absolute left-3 top-3 badge border-brand-200 bg-white text-brand-700">Nouveau</span>
                            @endunless
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="mt-12 grid gap-8 lg:grid-cols-2">
            <section>
                <div class="flex items-end justify-between gap-4">
                    <h2 class="text-xl font-bold tracking-tight text-ink-900">Mes derniers favoris</h2>
                    <a href="{{ route('favorites.index') }}" class="text-sm font-medium text-brand-700 hover:underline">Tout voir</a>
                </div>

                @if ($favorites->isEmpty())
                    <p class="mt-4 rounded-2xl border border-dashed border-ink-300 p-6 text-center text-sm text-ink-500">
                        Aucun favori enregistre.
                    </p>
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach ($favorites as $favorite)
                            <li wire:key="fav-{{ $favorite->id }}" class="card flex items-center gap-4 p-3">
                                <span class="h-16 w-24 shrink-0 overflow-hidden rounded-xl bg-ink-100">
                                    @if ($favorite->vehicle->mainImage)
                                        <img src="{{ $favorite->vehicle->mainImage->url }}" alt="" class="h-full w-full object-cover">
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <a href="{{ route('vehicles.show', $favorite->vehicle) }}"
                                       class="block truncate font-medium text-ink-900 hover:text-brand-700">
                                        {{ $favorite->vehicle->title() }}
                                    </a>
                                    <span class="text-sm text-brand-700">{{ number_format($favorite->vehicle->price, 0, ',', ' ') }} FCFA</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section>
                <h2 class="text-xl font-bold tracking-tight text-ink-900">Vous pourriez aussi aimer</h2>
                <p class="mt-1 text-sm text-ink-500">Selection hors de vos alertes actuelles.</p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($suggestions as $suggestion)
                        <div wire:key="sug-{{ $suggestion->id }}" class="card overflow-hidden">
                            <a href="{{ route('vehicles.show', $suggestion) }}" class="block">
                                <span class="block aspect-[4/3] bg-ink-100">
                                    @if ($suggestion->mainImage)
                                        <img src="{{ $suggestion->mainImage->url }}" alt="" class="h-full w-full object-cover">
                                    @endif
                                </span>
                                <span class="block p-3">
                                    <span class="block text-sm font-medium text-ink-900">{{ $suggestion->title() }}</span>
                                    <span class="block text-sm text-brand-700">{{ number_format($suggestion->price, 0, ',', ' ') }} FCFA</span>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>