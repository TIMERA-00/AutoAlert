<x-layouts.app>
    <div class="container-app py-10">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink-900">Apercu de l annonce</h1>
                <p class="mt-1 text-sm text-ink-500">Rendu public, tel que les visiteurs le verront.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn-secondary">Modifier</a>
                <a href="{{ route('admin.vehicles.index') }}" class="btn-ghost">Retour</a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="card overflow-hidden">
                @if ($vehicle->mainImage)
                    <img src="{{ $vehicle->mainImage->url }}" alt="" class="aspect-[16/9] w-full object-cover">
                @else
                    <div class="grid aspect-[16/9] place-items-center bg-ink-100 text-ink-300">Aucune image</div>
                @endif

                <div class="space-y-4 p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-xl font-bold text-ink-900">{{ $vehicle->title() }}</h2>
                        <span class="badge {{ $vehicle->status->badgeClass() }}">{{ $vehicle->status->label() }}</span>
                    </div>
                    <p class="text-2xl font-bold text-brand-700">{{ number_format($vehicle->price, 0, ',', ' ') }} FCFA</p>
                    <p class="whitespace-pre-line text-sm text-ink-600">{{ $vehicle->description ?: 'Aucune description.' }}</p>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="card p-5 text-sm">
                    <h3 class="font-semibold text-ink-900">Controle avant publication</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach ([
                            ['Photos', $vehicle->images->isNotEmpty(), 'Ajoutez au moins une image'],
                            ['Prix renseigne', $vehicle->price > 0, 'Le prix est obligatoire'],
                            ['Description', filled($vehicle->description), 'Ajoutez une description'],
                            ['Source', filled($vehicle->source_url), 'Renseignez l URL de l annonce originale'],
                            ['Localisation', filled($vehicle->location), 'Renseignez la ville'],
                        ] as [$label, $ok, $hint])
                            <li class="flex items-start gap-2">
                                <span class="mt-0.5 {{ $ok ? 'text-emerald-600' : 'text-amber-500' }}">
                                    @if ($ok)
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0z"/></svg>
                                    @else
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.3 3.3a1 1 0 0 1 1.4 0l7 7a1 1 0 0 1 0 1.4l-7 7a1 1 0 1 1-1.4-1.4L15 11 8.3 4.3a1 1 0 0 1 0-1z"/></svg>
                                    @endif
                                </span>
                                <span class="{{ $ok ? 'text-ink-700' : 'text-amber-700' }}">
                                    {{ $label }} — <span class="text-xs">{{ $ok ? 'OK' : $hint }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card p-5 text-sm">
                    <h3 class="font-semibold text-ink-900">Prochaine etape</h3>
                    <p class="mt-2 text-ink-600">
                        @if ($vehicle->status->value === 'DRAFT')
                            Publiez l annonce pour notifier les alertes correspondantes.
                        @elseif ($vehicle->status->value === 'PUBLISHED')
                            L annonce est visible et les alertes ont ete traitees.
                        @else
                            Statut actuel : {{ $vehicle->status->label() }}.
                        @endif
                    </p>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.app>
