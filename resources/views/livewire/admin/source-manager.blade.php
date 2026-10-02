<div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-ink-900">Sources</h1>
            <p class="mt-1 text-sm text-ink-500">
                Une source represente un site ou un partenaire dont proviennent les annonces.
                {{ $vehiclesWithoutSource }} vehicule(s) sans source associee.
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[380px_minmax(0,1fr)]">
            <form wire:submit="save" class="card h-fit space-y-4 p-6">
                <h2 class="font-semibold text-ink-900">{{ $editing ? 'Modifier la source' : 'Nouvelle source' }}</h2>

                <div>
                    <label class="label" for="s-name">Nom *</label>
                    <input id="s-name" type="text" wire:model="name" class="input @error('name') border-red-400 @enderror" placeholder="Source A">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="s-url">URL *</label>
                    <input id="s-url" type="url" wire:model="baseUrl" class="input @error('baseUrl') border-red-400 @enderror" placeholder="https://example.com">
                    @error('baseUrl') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="s-type">Type</label>
                    <select id="s-type" wire:model="type" class="input">
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-center gap-3">
                    <input type="checkbox" wire:model="isActive" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm text-ink-700">Source active</span>
                </label>

                <div>
                    <label class="label" for="s-notes">Notes</label>
                    <textarea id="s-notes" rows="3" wire:model="notes" class="input" placeholder="Conditions d utilisation, contact..."></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1">
                        {{ $editing ? 'Mettre a jour' : 'Ajouter' }}
                    </button>
                    @if ($editing)
                        <button type="button" wire:click="resetForm" class="btn-secondary">Annuler</button>
                    @endif
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
                <table class="min-w-full divide-y divide-ink-200 text-sm">
                    <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Source</th>
                            <th class="hidden px-4 py-3 font-semibold sm:table-cell">Type</th>
                            <th class="px-4 py-3 font-semibold">Vehicules</th>
                            <th class="px-4 py-3 font-semibold">Statut</th>
                            <th class="px-4 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($sources as $source)
                            <tr wire:key="src-{{ $source->id }}" class="hover:bg-ink-50/60">
                                <td class="px-4 py-3">
                                    <span class="block font-medium text-ink-900">{{ $source->name }}</span>
                                    <span class="block text-xs text-ink-500">{{ $source->host() }}</span>
                                </td>
                                <td class="hidden px-4 py-3 text-ink-600 sm:table-cell">{{ $source->type->label() }}</td>
                                <td class="px-4 py-3 text-ink-900">{{ $source->vehicles_count }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $source->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-ink-200 bg-ink-100 text-ink-500' }}">
                                        {{ $source->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <button type="button" wire:click="edit({{ $source->id }})" class="btn-secondary btn-sm">Modifier</button>
                                        <button type="button" wire:click="toggle({{ $source->id }})" class="btn-secondary btn-sm">
                                            {{ $source->is_active ? 'Desactiver' : 'Activer' }}
                                        </button>
                                        <button type="button" wire:click="delete({{ $source->id }})"
                                                wire:confirm="Supprimer cette source ?" class="btn-danger btn-sm">Supprimer</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-ink-500">
                                    Aucune source enregistree. Ajoutez la premiere pour tracer l origine des annonces.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-toast-host />
</div>
