@php
    use App\Enums\VehicleStatus;
    use App\Models\Vehicle;
    use App\Services\StatisticsService;

    $stats = app(StatisticsService::class);
    $featured = Vehicle::published()->with('images')->orderByDesc('published_at')->limit(8)->get();
    $stats = $stats->publicStats();
@endphp

<x-layouts.app>
    <section class="border-b border-ink-200 bg-gradient-to-br from-brand-600 via-brand-700 to-ink-900">
        <div class="container-app grid gap-10 py-16 lg:grid-cols-2 lg:items-center lg:py-24">
            <div>
                <span class="badge border-white/25 bg-white/10 text-white">Centralisation d'annonces</span>

                <h1 class="mt-5 text-4xl font-bold tracking-tight text-white sm:text-5xl">
                    Le vehicule que vous cherchez,<br>
                    des que quelqu'un le publie.
                </h1>

                <p class="mt-5 max-w-xl text-lg text-brand-100">
                    AutoAlert regroupe les vehicules a vendre, analyse vos criteres de recherche et vous previent
                    automatiquement. Vous ne surveillez plus le marche : la plateforme le fait pour vous.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('vehicles.index') }}" class="btn bg-white px-6 text-brand-700 hover:bg-brand-50">
                        Explorer le catalogue
                    </a>
                    <a href="{{ auth()->check() ? route('alerts.create') : route('register') }}"
                       class="btn border border-white/30 px-6 text-white hover:bg-white/10">
                        Creer une alerte
                    </a>
                </div>

                <dl class="mt-12 grid max-w-lg grid-cols-3 gap-6 text-white">
                    <div>
                        <dt class="text-sm text-brand-200">Vehicules</dt>
                        <dd class="text-2xl font-bold">{{ number_format($stats['vehicles']) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-brand-200">Marques</dt>
                        <dd class="text-2xl font-bold">{{ number_format($stats['brands']) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-brand-200">Nouveautes / 7j</dt>
                        <dd class="text-2xl font-bold">{{ number_format($stats['updates_this_week']) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="hidden lg:block">
                <div class="card overflow-hidden p-6">
                    <h2 class="text-sm font-semibold text-ink-900">Comment ca marche</h2>
                    <ol class="mt-5 space-y-5">
                        @foreach ([
                            ['1.', 'Creez une alerte', 'Indiquez marque, budget, annee, carburant : 30 secondes suffisent.'],
                            ['2.', 'Nous surveillons le marche', 'A chaque publication, les criteres sont compares automatiquement.'],
                            ['3.', 'Vous etes prevenu', 'Email immediat, resume quotidien ou WhatsApp, selon vos preferences.'],
                        ] as [$number, $title, $text])
                            <li class="flex gap-4">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">{{ $number }}</span>
                                <span>
                                    <span class="block font-semibold text-ink-900">{{ $title }}</span>
                                    <span class="mt-0.5 block text-sm text-ink-500">{{ $text }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="container-app py-16">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-ink-900">Derniers vehicules disponibles</h2>
                <p class="mt-1 text-sm text-ink-500">Les annonces les plus recentes publiees sur la plateforme.</p>
            </div>
            <a href="{{ route('vehicles.index') }}" class="btn-secondary btn-sm">Voir tout le catalogue</a>
        </div>

        @if ($featured->isEmpty())
            <div class="mt-8">
                <x-empty-state title="Aucun vehicule pour le moment"
                               description="Les annonces apparaissent ici des qu'elles sont publiees. Creez une alerte pour etre prevenu en premier.">
                    <a href="{{ auth()->check() ? route('alerts.create') : route('register') }}" class="btn-primary mt-2">Creer une alerte</a>
                </x-empty-state>
            </div>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featured as $vehicle)
                    <x-vehicle-card :vehicle="$vehicle" />
                @endforeach
            </div>
        @endif
    </section>

    <section class="border-y border-ink-200 bg-white">
        <div class="container-app grid gap-10 py-16 lg:grid-cols-3">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-ink-900">Pourquoi AutoAlert</h2>
                <p class="mt-3 text-ink-600">
                    Les annonces sont dispersees sur de nombreux sites. AutoAlert les centralise, les nettoie,
                    et vous alerte au bon moment.
                </p>
            </div>

            @foreach ([
                ['Recherche ciblee', 'Dix filtres combinables : marque, budget, annee, kilometrage, carburant, boite, carrosserie, localisation.'],
                ['Alertes illimitees', 'Autant de recherches que vous le souhaitez, chacune avec sa propre frequence de notification.'],
                ['Annonce originale', 'Chaque fiche renvoie vers la source : vous verifiez l information sur place, chez le vendeur.'],
            ] as [$title, $text])
                <div class="card p-6">
                    <h3 class="font-semibold text-ink-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-ink-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="container-app py-16 text-center">
        <h2 class="text-2xl font-bold tracking-tight text-ink-900">Arretez de rafraichir 15 sites</h2>
        <p class="mx-auto mt-3 max-w-2xl text-ink-600">
            Dites nous ce que vous cherchez. Des qu'un vehicule correspond a vos criteres, vous le savez.
        </p>
        <a href="{{ auth()->check() ? route('alerts.create') : route('register') }}" class="btn-primary mt-6 px-6">
            {{ auth()->check() ? 'Creer une alerte' : 'Creer mon compte gratuit' }}
        </a>
    </section>
</x-layouts.app>