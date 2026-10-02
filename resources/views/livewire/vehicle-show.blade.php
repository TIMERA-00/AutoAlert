<div>
        <div class="container-app py-8">
            <nav class="mb-6 text-sm text-ink-500" aria-label="Fil d Ariane">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-brand-700">Accueil</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('vehicles.index') }}" class="hover:text-brand-700">Catalogue</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('vehicles.index', ['marque' => $vehicle->brand]) }}" class="hover:text-brand-700">{{ $vehicle->brand }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="font-medium text-ink-700" aria-current="page">{{ $vehicle->title() }}</li>
                </ol>
            </nav>

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_380px]">
                {{-- Gallery --}}
                <div>
                    <div class="card overflow-hidden">
                        <div class="aspect-[16/10] bg-ink-100">
                            @if ($vehicle->images->isNotEmpty())
                                <img src="{{ $vehicle->images[$activeImage]->url }}"
                                     alt="{{ $vehicle->images[$activeImage]->alt ?? $vehicle->title() }}"
                                     class="h-full w-full object-cover">
                            @else
                                <div class="grid h-full w-full place-items-center text-ink-300">
                                    <svg class="h-24 w-24" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M2.25 12.75l1.5-4.5A2.25 2.25 0 0 1 5.9 6.75h12.2a2.25 2.25 0 0 1 2.15 1.5l1.5 4.5M2.25 12.75h19.5m-19.5 0v3.75A2.25 2.25 0 0 0 5.25 18.75h13.5a2.25 2.25 0 0 0 2.25-2.25v-3.75"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        @if ($vehicle->images->count() > 1)
                            <div class="flex gap-3 overflow-x-auto p-4">
                                @foreach ($vehicle->images as $index => $image)
                                    <button type="button"
                                            wire:click="$set('activeImage', {{ $index }})"
                                            class="h-20 w-24 shrink-0 overflow-hidden rounded-lg border-2 transition {{ $index === $activeImage ? 'border-brand-600' : 'border-transparent hover:border-ink-300' }}"
                                            aria-label="Voir l image {{ $index + 1 }}">
                                        <img src="{{ $image->url }}" alt="" class="h-full w-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="card mt-6 p-6">
                        <h2 class="text-lg font-semibold text-ink-900">Description</h2>
                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink-600">
                            {{ $vehicle->description ?: 'Le vendeur n a pas fourni de description pour ce vehicule.' }}
                        </p>

                        <dl class="mt-6 grid gap-x-6 gap-y-3 border-t border-ink-100 pt-6 sm:grid-cols-2">
                            @foreach ([
                                'Marque' => $vehicle->brand,
                                'Modele' => $vehicle->model,
                                'Annee' => $vehicle->year,
                                'Prix' => number_format($vehicle->price, 0, ',', ' ').' FCFA',
                                'Kilometrage' => number_format($vehicle->mileage, 0, ',', ' ').' km',
                                'Carburant' => $vehicle->fuel->label(),
                                'Boite de vitesse' => $vehicle->transmission->label(),
                                'Carrosserie' => $vehicle->body_type->label(),
                                'Couleur' => $vehicle->color ?: '-',
                                'Localisation' => $vehicle->location ?: '-',
                                'Reference' => $vehicle->reference ?: '-',
                                'Ajoute le' => $vehicle->created_at->translatedFormat('d F Y'),
                            ] as $label => $value)
                                <div class="flex justify-between gap-4 border-b border-dashed border-ink-100 pb-2">
                                    <dt class="text-sm text-ink-500">{{ $label }}</dt>
                                    <dd class="text-right text-sm font-medium text-ink-900">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>

                {{-- Sidebar --}}
                <aside class="lg:sticky lg:top-24 lg:h-fit">
                    <div class="card p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight text-ink-900">{{ $vehicle->title() }}</h1>
                                <p class="mt-1 text-sm text-ink-500">
                                    {{ $vehicle->location ?? 'Senegal' }} · publie {{ $vehicle->published_at?->translatedFormat('d F Y') ?? '-' }}
                                </p>
                            </div>
                            <span class="badge {{ $vehicle->status->badgeClass() }}">{{ $vehicle->status->label() }}</span>
                        </div>

                        <p class="mt-5 text-3xl font-bold text-ink-900">{{ number_format($vehicle->price, 0, ',', ' ') }} FCFA</p>

                        <div class="mt-6 space-y-2">
                            @if ($vehicle->source_url)
                                <a href="{{ route('source.redirect', $vehicle) }}" class="btn-primary w-full">
                                    Voir l annonce originale
                                </a>
                            @endif

                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" wire:click="toggleFavorite"
                                        class="{{ $isFavorite ? 'btn border border-red-200 bg-red-50 text-red-700' : 'btn-secondary' }}"
                                        aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
                                    <svg class="h-4 w-4" fill="{{ $isFavorite ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                                    </svg>
                                    {{ $isFavorite ? 'En favori' : 'Favori' }}
                                </button>

                                <a href="{{ route('alerts.create') }}?vehicule={{ $vehicle->id }}" class="btn-secondary">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31"/>
                                    </svg>
                                    Creer une alerte
                                </a>
                            </div>
                        </div>

                        <div x-data="{ copied: false }" class="mt-4 border-t border-ink-100 pt-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-ink-400">Partager</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <a href="{{ $this->shareLinks['whatsapp'] }}" target="_blank" rel="noopener"
                                   class="btn-secondary btn-sm">WhatsApp</a>
                                <a href="{{ $this->shareLinks['facebook'] }}" target="_blank" rel="noopener"
                                   class="btn-secondary btn-sm">Facebook</a>
                                <a href="{{ $this->shareLinks['x'] }}" target="_blank" rel="noopener"
                                   class="btn-secondary btn-sm">X</a>
                                <button type="button"
                                        x-on:click="navigator.clipboard.writeText('{{ $this->shareLinks['copy'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="btn-secondary btn-sm">
                                    <span x-text="copied ? 'Lien copie !' : 'Copier le lien'"></span>
                                </button>
                            </div>
                        </div>

                        <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-ink-100 pt-4 text-center">
                            <div class="rounded-xl bg-ink-50 py-3">
                                <dt class="text-xs text-ink-500">Vues</dt>
                                <dd class="text-lg font-semibold text-ink-900">{{ number_format($vehicle->view_count) }}</dd>
                            </div>
                            <div class="rounded-xl bg-ink-50 py-3">
                                <dt class="text-xs text-ink-500">Favoris</dt>
                                <dd class="text-lg font-semibold text-ink-900">{{ number_format($vehicle->favorite_count) }}</dd>
                            </div>
                        </dl>

                        <p class="mt-4 text-xs text-ink-400">
                            AutoAlert centralise l annonce : les informations et le prix sont a confirmer avec le vendeur.
                        </p>
                    </div>
                </aside>
            </div>

            @if ($similar->isNotEmpty())
                <section class="mt-14">
                    <h2 class="text-xl font-bold tracking-tight text-ink-900">Vehicules similaires</h2>
                    <p class="mt-1 text-sm text-ink-500">Vous pourriez egalement etre interesse par :</p>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($similar as $item)
                            <x-vehicle-card :vehicle="$item" wire:key="similar-{{ $item->id }}" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <x-toast-host />
</div>
