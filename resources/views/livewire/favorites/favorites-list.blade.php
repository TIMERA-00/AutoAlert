<div>
        <div class="container-app py-10">
            <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-ink-900">Mes favoris</h1>
                    <p class="mt-1 text-sm text-ink-500">{{ $favorites->total() }} vehicule{{ $favorites->total() > 1 ? 's' : '' }} enregistre{{ $favorites->total() > 1 ? 's' : '' }}.</p>
                </div>

                <select wire:model.live="sort" class="input w-auto" aria-label="Trier les favoris">
                    <option value="recent">Plus recents</option>
                    <option value="price_asc">Prix croissant</option>
                    <option value="price_desc">Prix decroissant</option>
                    <option value="year_desc">Annee</option>
                    <option value="mileage_asc">Kilometrage</option>
                </select>
            </header>

            @if ($favorites->isEmpty())
                <x-empty-state title="Aucun favori pour le moment"
                               description="Cliquez sur le bouton Favori sur une annonce pour la retrouver ici.">
                    <a href="{{ route('vehicles.index') }}" class="btn-primary mt-2">Parcourir le catalogue</a>
                </x-empty-state>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($favorites as $favorite)
                        <div wire:key="fav-{{ $favorite->id }}" class="relative">
                            <x-vehicle-card :vehicle="$favorite->vehicle" />
                            <button type="button" wire:click="remove({{ $favorite->vehicle_id }})"
                                    wire:confirm="Retirer ce vehicule de vos favoris ?"
                                    class="absolute right-3 top-3 rounded-lg bg-white/90 p-2 text-red-600 shadow-sm backdrop-blur hover:bg-red-50"
                                    aria-label="Retirer des favoris">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21s-9-4.35-9-10a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 5.65-9 10-9 10z"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="mt-10">{{ $favorites->links() }}</div>
            @endif
        </div>

        <x-toast-host />
</div>
