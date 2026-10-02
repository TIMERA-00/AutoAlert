<div>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink-900">
                    {{ $vehicle ? 'Modifier le vehicule' : 'Ajouter un vehicule' }}
                </h1>
                <p class="mt-1 text-sm text-ink-500">
                    {{ $vehicle ? $vehicle->title().' · '.$vehicle->status->label() : 'Saisie manuelle ou import depuis une URL.' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($vehicle)
                    <a href="{{ route('admin.vehicles.preview', $vehicle) }}" class="btn-secondary">Apercu public</a>
                @endif
                <a href="{{ route('admin.vehicles.index') }}" class="btn-ghost">Retour a la liste</a>
            </div>
        </div>

        <form wire:submit="save" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-6">
                <div class="card space-y-5 p-6">
                    <h2 class="font-semibold text-ink-900">Identification</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="brand">Marque *</label>
                            <input id="brand" type="text" wire:model="brand" class="input @error('brand') border-red-400 @enderror" placeholder="Toyota">
                            @error('brand') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="model">Modele *</label>
                            <input id="model" type="text" wire:model="model" class="input @error('model') border-red-400 @enderror" placeholder="RAV4">
                            @error('model') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="label" for="year">Annee *</label>
                            <input id="year" type="number" wire:model="year" class="input @error('year') border-red-400 @enderror" placeholder="2023">
                            @error('year') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="price">Prix (FCFA) *</label>
                            <input id="price" type="number" wire:model="price" class="input @error('price') border-red-400 @enderror" placeholder="19500000">
                            @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="mileage">Kilometrage *</label>
                            <input id="mileage" type="number" wire:model="mileage" class="input @error('mileage') border-red-400 @enderror" placeholder="47000">
                            @error('mileage') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="card space-y-5 p-6">
                    <h2 class="font-semibold text-ink-900">Caracteristiques</h2>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="label" for="fuel">Carburant</label>
                            <select id="fuel" wire:model="fuel" class="input">
                                @foreach ($fuelOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="transmission">Boite de vitesse</label>
                            <select id="transmission" wire:model="transmission" class="input">
                                @foreach ($transmissionOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="bodyType">Carrosserie</label>
                            <select id="bodyType" wire:model="bodyType" class="input">
                                @foreach ($bodyTypeOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="color">Couleur</label>
                            <input id="color" type="text" wire:model="color" class="input" placeholder="Blanc">
                        </div>
                        <div>
                            <label class="label" for="location">Localisation</label>
                            <input id="location" type="text" wire:model="location" class="input" placeholder="Dakar">
                        </div>
                    </div>

                    <div>
                        <label class="label" for="description">Description</label>
                        <textarea id="description" rows="6" wire:model="description" class="input"
                                  placeholder="Etat general, options, controles effectues..."></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="card space-y-5 p-6">
                    <h2 class="font-semibold text-ink-900">Source</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="sourceId">Source reference</label>
                            <select id="sourceId" wire:model="sourceId" class="input">
                                <option value="">Aucune</option>
                                @foreach ($sources as $source)
                                    <option value="{{ $source->id }}">{{ $source->name }} ({{ $source->host() }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="sourceUrl">URL de l annonce originale</label>
                            <input id="sourceUrl" type="url" wire:model="sourceUrl" class="input @error('sourceUrl') border-red-400 @enderror"
                                   placeholder="https://source.com/annonce/123">
                            @error('sourceUrl') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="card space-y-5 p-6">
                    <div>
                        <h2 class="font-semibold text-ink-900">Images</h2>
                        <p class="mt-1 text-sm text-ink-500">
                            Stockage : {{ $imageProvider === 'cloudinary' ? 'Cloudinary (compression + CDN)' : 'Disque local (storage/app/public)' }}.
                            JPEG, PNG ou WebP, 5 Mo maximum par image.
                        </p>
                    </div>

                    <label class="flex cursor-pointer items-center justify-center gap-3 rounded-xl border-2 border-dashed border-ink-300 px-4 py-8 text-sm text-ink-500 transition hover:border-brand-400 hover:text-brand-700"
                           wire:loading.class="opacity-50">
                        <input type="file" multiple accept="image/jpeg,image/png,image/webp" wire:model="images" class="sr-only">
                        <span wire:loading.remove wire:target="images">Cliquer pour selectionner des images</span>
                        <span wire:loading wire:target="images">Telechargement en cours...</span>
                    </label>

                    @error('images.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('images') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    <ul wire:key="images-{{ $vehicle?->id ?? 'new' }}" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach ($vehicle?->images ?? [] as $image)
                            <li wire:key="img-{{ $image->id }}" class="overflow-hidden rounded-xl border border-ink-200 bg-white">
                                <div class="relative">
                                    <img src="{{ $image->url }}" alt="" class="aspect-[4/3] w-full object-cover">
                                    @if ($image->is_primary)
                                        <span class="badge absolute left-2 top-2 border-brand-200 bg-white text-brand-700">Principale</span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-1 p-2">
                                    <button type="button" wire:click="makePrimary({{ $image->id }})" class="btn-ghost btn-sm" title="Definir comme principale">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3 7h7l-5.5 4.5L18.5 21 12 16.5 5.5 21l2-7.5L2 9h7z"/></svg>
                                    </button>
                                    <button type="button" wire:click="moveImage({{ $image->id }}, -1)" class="btn-ghost btn-sm" title="Deplacer avant">&uarr;</button>
                                    <button type="button" wire:click="moveImage({{ $image->id }}, 1)" class="btn-ghost btn-sm" title="Deplacer apres">&darr;</button>
                                    <button type="button" wire:click="removeImage({{ $image->id }})"
                                            wire:confirm="Supprimer cette image ?" class="btn-ghost btn-sm text-red-600" title="Supprimer">&times;</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <aside class="lg:sticky lg:top-24 lg:h-fit">
                <div class="card space-y-5 p-6">
                    <div>
                        <label class="label" for="status">Statut</label>
                        <select id="status" wire:model="status" class="input @error('status') border-red-400 @enderror">
                            @foreach ($allowedTransitions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                        @if ($vehicle)
                            <p class="mt-2 text-xs text-ink-500">
                                Workflow : DRAFT &rarr; PUBLISHED &rarr; RESERVED &rarr; SOLD &rarr; ARCHIVED.
                                Transitions autorisees depuis « {{ $vehicle->status->label() }} » : {{ implode(', ', array_values($allowedTransitions)) ?: 'aucune' }}.
                            </p>
                        @else
                            <p class="mt-2 text-xs text-ink-500">
                                Publier declenche immediatement le matching avec les alertes des utilisateurs.
                            </p>
                        @endif
                    </div>

                    <div class="rounded-xl bg-ink-50 p-4 text-xs text-ink-600">
                        <p class="font-medium text-ink-800">Enregistrement</p>
                        @if ($vehicle)
                            <p class="mt-1">Cree le {{ $vehicle->created_at->translatedFormat('d F Y') }}</p>
                            <p>Modifie le {{ $vehicle->updated_at->diffForHumans() }}</p>
                            <p>{{ number_format($vehicle->view_count) }} vue(s) · {{ number_format($vehicle->favorite_count) }} favori(s)</p>
                        @else
                            <p class="mt-1">Une reference sera generee automatiquement.</p>
                        @endif
                    </div>

                    <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">{{ $vehicle ? 'Enregistrer' : 'Creer le vehicule' }}</span>
                        <span wire:loading wire:target="save">Enregistrement...</span>
                    </button>

                    <a href="{{ route('admin.vehicles.index') }}" class="btn-ghost w-full">Annuler</a>
                </div>
            </aside>
        </form>

        <x-toast-host />
</div>
