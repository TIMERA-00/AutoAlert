<div>
        <div class="container-app py-10">
            <header class="mb-6">
                <h1 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Catalogue des vehicules</h1>
                <p class="mt-1 text-sm text-ink-500">
                    {{ $vehicles->total() }} vehicule{{ $vehicles->total() > 1 ? 's' : '' }} disponible{{ $vehicles->total() > 1 ? 's' : '' }} a la vente.
                </p>
            </header>

            <div class="grid gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">
                {{-- Filters --}}
                <aside class="lg:sticky lg:top-24 lg:h-fit">
                    <form wire:submit="resetPage" class="card p-5">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold text-ink-900">Filtres</h2>
                            @if ($this->hasActiveFilters)
                                <button type="button" wire:click="clearFilters" class="text-xs font-medium text-brand-700 hover:underline">
                                    Tout effacer
                                </button>
                            @endif
                        </div>

                        <div class="mt-5 space-y-5">
                            <div>
                                <label class="label" for="brand">Marque</label>
                                <select id="brand" wire:model.live="brand" class="input">
                                    <option value="">Toutes les marques</option>
                                    @foreach ($this->brands() as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="label" for="location">Localisation</label>
                                <select id="location" wire:model.live="location" class="input">
                                    <option value="">Toutes les villes</option>
                                    @foreach ($this->locations() as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="label" for="minPrice">Prix min</label>
                                    <input id="minPrice" type="number" min="0" step="500000" wire:model.live.debounce.600ms="minPrice"
                                           class="input" placeholder="0">
                                </div>
                                <div>
                                    <label class="label" for="maxPrice">Prix max</label>
                                    <input id="maxPrice" type="number" min="0" step="500000" wire:model.live.debounce.600ms="maxPrice"
                                           class="input" placeholder="{{ $this->priceBounds()['max'] }}">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="label" for="minYear">Annee min</label>
                                    <input id="minYear" type="number" min="1900" max="{{ date('Y') + 2 }}" wire:model.live.debounce.600ms="minYear"
                                           class="input" placeholder="2021">
                                </div>
                                <div>
                                    <label class="label" for="maxMileage">Km max</label>
                                    <input id="maxMileage" type="number" min="0" step="10000" wire:model.live.debounce.600ms="maxMileage"
                                           class="input" placeholder="100000">
                                </div>
                            </div>

                            <fieldset>
                                <legend class="label">Carburant</legend>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($fuelOptions as $value => $label)
                                        <button type="button"
                                                wire:click="toggleFilter('fuel', '{{ $value }}')"
                                                class="chip {{ in_array($value, $fuel) ? 'chip-active' : '' }}">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </fieldset>

                            <fieldset>
                                <legend class="label">Boite de vitesse</legend>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($transmissionOptions as $value => $label)
                                        <button type="button"
                                                wire:click="toggleFilter('transmission', '{{ $value }}')"
                                                class="chip {{ in_array($value, $transmission) ? 'chip-active' : '' }}">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </fieldset>

                            <fieldset>
                                <legend class="label">Carrosserie</legend>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($bodyTypeOptions as $value => $label)
                                        <button type="button"
                                                wire:click="toggleFilter('bodyType', '{{ $value }}')"
                                                class="chip {{ in_array($value, $bodyType) ? 'chip-active' : '' }}">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </fieldset>

                            <button type="submit" class="btn-primary w-full">Appliquer</button>

                            @auth
                                <button type="button" wire:click="createAlertFromFilters" class="btn-secondary w-full">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0"/>
                                    </svg>
                                    Creer une alerte avec ces criteres
                                </button>
                            @else
                                <p class="text-center text-xs text-ink-500">
                                    <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:underline">Connectez-vous</a>
                                    pour transformer ces criteres en alerte.
                                </p>
                            @endauth
                        </div>
                    </form>
                </aside>

                {{-- Results --}}
                <section aria-live="polite">
                    <div class="card mb-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="relative flex-1">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400"
                                 fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                            </svg>
                            <input type="search" wire:model.live.debounce.500ms="search" placeholder="Rechercher une marque, un modele, une ville..."
                                   class="input pl-10" aria-label="Rechercher un vehicule">
                        </div>

                        <div class="flex items-center gap-2">
                            <label for="sort" class="text-sm text-ink-500">Trier</label>
                            <select id="sort" wire:model.live="sort" class="input w-auto">
                                @foreach ($sortOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($this->hasActiveFilters)
                        <div class="mb-4 flex flex-wrap items-center gap-2 text-xs text-ink-600">
                            <span class="font-medium">Filtres :</span>
                            @if ($search)<span class="badge border-ink-200 bg-white">Recherche : {{ $search }}</span>@endif
                            @if ($brand)<span class="badge border-ink-200 bg-white">Marque : {{ $brand }}</span>@endif
                            @if ($location)<span class="badge border-ink-200 bg-white">{{ $location }}</span>@endif
                            @if ($minPrice)<span class="badge border-ink-200 bg-white">&ge; {{ number_format((int) $minPrice) }}</span>@endif
                            @if ($maxPrice)<span class="badge border-ink-200 bg-white">&le; {{ number_format((int) $maxPrice) }}</span>@endif
                            @if ($minYear)<span class="badge border-ink-200 bg-white">&ge; {{ $minYear }}</span>@endif
                            @if ($maxMileage)<span class="badge border-ink-200 bg-white">&le; {{ number_format((int) $maxMileage) }} km</span>@endif
                            @foreach (array_merge($fuel, $transmission, $bodyType) as $value)
                                <span class="badge border-ink-200 bg-white">{{ ucfirst(strtolower(str_replace('_', ' ', $value))) }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div wire:loading.flex class="mb-4 items-center gap-2 text-sm text-ink-500">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        Recherche en cours...
                    </div>

                    @if ($vehicles->isEmpty())
                        <x-empty-state title="Aucun vehicule ne correspond"
                                       description="Essayez d elargir vos criteres ou creez une alerte pour etre prevenu des que le vehicule ideal apparait.">
                            <div class="mt-2 flex gap-2">
                                <button type="button" wire:click="clearFilters" class="btn-secondary">Effacer les filtres</button>
                                <a href="{{ auth()->check() ? route('alerts.create') : route('register') }}" class="btn-primary">Creer une alerte</a>
                            </div>
                        </x-empty-state>
                    @else
                        <div wire:key="results-{{ $vehicles->total() }}-{{ implode('-', $fuel) }}-{{ implode('-', $transmission) }}"
                             class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($vehicles as $vehicle)
                                <x-vehicle-card :vehicle="$vehicle" wire:key="vehicle-{{ $vehicle->id }}" />
                            @endforeach
                        </div>

                        <div class="mt-10">
                            {{ $vehicles->links() }}
                        </div>
                    @endif
                </section>
            </div>
        </div>

        <x-toast-host />
</div>
