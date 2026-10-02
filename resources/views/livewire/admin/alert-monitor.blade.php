<div>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink-900">Alertes</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $active }} active(s) sur {{ $total }}.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <input type="search" wire:model.live.debounce.400ms="search" class="input w-64"
                       placeholder="Nom d alerte ou email..." aria-label="Rechercher une alerte">
                <select wire:model.live="frequency" class="input w-auto" aria-label="Filtrer par frequence">
                    <option value="">Toutes frequences</option>
                    @foreach ($frequencyOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <table class="min-w-full divide-y divide-ink-200 text-sm">
                <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Alerte</th>
                        <th class="hidden px-4 py-3 font-semibold lg:table-cell">Utilisateur</th>
                        <th class="px-4 py-3 font-semibold">Frequence</th>
                        <th class="px-4 py-3 font-semibold">Notifs</th>
                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($alerts as $alert)
                        <tr wire:key="alert-{{ $alert->id }}" class="hover:bg-ink-50/60">
                            <td class="px-4 py-3">
                                <span class="block font-medium text-ink-900">{{ $alert->name }}</span>
                                <span class="block text-xs text-ink-500">{{ $alert->summary() }}</span>
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                <span class="block text-ink-800">{{ $alert->user?->fullName() ?? 'Compte supprime' }}</span>
                                <span class="block text-xs text-ink-500">{{ $alert->user?->email }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge border-ink-200 bg-white text-ink-600">{{ $alert->frequency->label() }}</span>
                            </td>
                            <td class="px-4 py-3 text-ink-900">{{ $alert->notifications_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" wire:click="toggle({{ $alert->id }})" class="btn-secondary btn-sm">
                                        {{ $alert->is_active ? 'Desactiver' : 'Activer' }}
                                    </button>
                                    <button type="button" wire:click="delete({{ $alert->id }})"
                                            wire:confirm="Supprimer cette alerte ?" class="btn-danger btn-sm">Supprimer</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-ink-500">Aucune alerte enregistree.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $alerts->links() }}</div>
</div>
