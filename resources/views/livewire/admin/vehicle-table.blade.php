<div>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink-900">Vehicules</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $total }} annonce(s) au total.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.vehicles.import') }}" class="btn-secondary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Importer depuis une URL
                </a>
                <a href="{{ route('admin.vehicles.create') }}" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Ajouter un vehicule
                </a>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
            @foreach ([
                'Tous' => $total,
                'PUBLISHED' => $counts['PUBLISHED'] ?? 0,
                'DRAFT' => $counts['DRAFT'] ?? 0,
                'RESERVED' => $counts['RESERVED'] ?? 0,
                'SOLD' => $counts['SOLD'] ?? 0,
            ] as $key => $count)
                @php $active = $status === ($key === 'Tous' ? '' : $key); @endphp
                <button type="button" wire:click="$set('status', '{{ $key === 'Tous' ? '' : $key }}')"
                        class="card px-4 py-3 text-left transition {{ $active ? 'ring-2 ring-brand-500' : 'hover:ring-1 hover:ring-ink-300' }}">
                    <span class="block text-xs uppercase tracking-wide text-ink-500">
                        {{ $key === 'Tous' ? 'Tous' : (\App\Enums\VehicleStatus::from($key)->label()) }}
                    </span>
                    <span class="mt-1 block text-xl font-bold text-ink-900">{{ $count }}</span>
                </button>
            @endforeach
        </div>

        <div class="card p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input type="search" wire:model.live.debounce.400ms="search" class="input flex-1"
                       placeholder="Rechercher par marque, modele, reference..." aria-label="Rechercher un vehicule">
                <select wire:model.live="sort" class="input w-auto" aria-label="Trier">
                    <option value="recent">Plus recents</option>
                    <option value="price_asc">Prix croissant</option>
                    <option value="price_desc">Prix decroissant</option>
                    <option value="year_desc">Annee</option>
                    <option value="mileage_asc">Kilometrage</option>
                </select>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <table class="min-w-full divide-y divide-ink-200 text-sm">
                <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Vehicule</th>
                        <th class="px-4 py-3 font-semibold">Prix</th>
                        <th class="hidden px-4 py-3 font-semibold md:table-cell">Statut</th>
                        <th class="hidden px-4 py-3 font-semibold lg:table-cell">Vues / Favoris</th>
                        <th class="hidden px-4 py-3 font-semibold lg:table-cell">Source</th>
                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($vehicles as $vehicle)
                        <tr wire:key="row-{{ $vehicle->id }}" class="hover:bg-ink-50/60">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="h-12 w-16 shrink-0 overflow-hidden rounded-lg bg-ink-100">
                                        @if ($vehicle->mainImage)
                                            <img src="{{ $vehicle->mainImage->url }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block font-medium text-ink-900">{{ $vehicle->title() }}</span>
                                        <span class="block text-xs text-ink-500">
                                            {{ $vehicle->year }} · {{ number_format($vehicle->mileage, 0, ',', ' ') }} km ·
                                            {{ $vehicle->transmission->label() }}
                                            @if ($vehicle->location) · {{ $vehicle->location }} @endif
                                        </span>
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-ink-900">
                                {{ number_format($vehicle->price, 0, ',', ' ') }}
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                <span class="badge {{ $vehicle->status->badgeClass() }}">{{ $vehicle->status->label() }}</span>
                            </td>
                            <td class="hidden px-4 py-3 text-ink-600 lg:table-cell">
                                {{ number_format($vehicle->view_count) }} / {{ number_format($vehicle->favorite_count) }}
                            </td>
                            <td class="hidden max-w-[16rem] truncate px-4 py-3 text-xs text-ink-500 lg:table-cell">
                                {{ $vehicle->source?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    @if ($vehicle->status->value === 'DRAFT')
                                        <button type="button" wire:click="publish({{ $vehicle->id }})" class="btn-primary btn-sm">Publier</button>
                                    @endif

                                    @if ($vehicle->isPublished())
                                        <button type="button" wire:click="changeStatus({{ $vehicle->id }}, 'RESERVED')" class="btn-secondary btn-sm">Reserver</button>
                                        <button type="button" wire:click="changeStatus({{ $vehicle->id }}, 'SOLD')" class="btn-secondary btn-sm">Vendu</button>
                                    @endif

                                    @if ($vehicle->status->value === 'PUBLISHED')
                                        <button type="button" wire:click="changeStatus({{ $vehicle->id }}, 'DRAFT')" class="btn-secondary btn-sm">Depublier</button>
                                    @endif

                                    <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn-secondary btn-sm">Modifier</a>
                                    <a href="{{ route('vehicles.show', $vehicle) }}" class="btn-secondary btn-sm">Voir</a>
                                    <button type="button" wire:click="delete({{ $vehicle->id }})"
                                            wire:confirm="Supprimer definitivement {{ $vehicle->title() }} ?"
                                            class="btn-danger btn-sm">Supprimer</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-ink-500">
                                Aucun vehicule ne correspond a cette recherche.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $vehicles->links() }}</div>

        <x-toast-host />
</div>
