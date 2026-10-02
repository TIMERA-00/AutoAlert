<div>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink-900">Importer une annonce</h1>
                <p class="mt-1 max-w-2xl text-sm text-ink-500">
                    Collez le lien de l annonce : le systeme extrait les informations disponibles, signale les doublons
                    puis vous laisse corriger avant publication.
                </p>
            </div>
            <a href="{{ route('admin.vehicles.index') }}" class="btn-ghost">Retour</a>
        </div>

        @unless ($importEnabled)
            <div class="alert-box mb-6 border-amber-200 bg-amber-50 text-amber-800">
                L import automatique est desactive (variable <code>IMPORT_ENABLED=false</code>).
                La saisie manuelle reste disponible.
            </div>
        @endunless

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-6">
                @if ($step === 'url')
                    <form wire:submit="analyse" class="card space-y-5 p-6">
                        <div>
                            <label class="label" for="url">URL de l annonce</label>
                            <input id="url" type="url" wire:model="url" class="input @error('url') border-red-400 @enderror"
                                   placeholder="https://source.com/annonce/12345">
                            @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @if ($error)
                            <div class="alert-box border-red-200 bg-red-50 text-red-800" role="alert">{{ $error }}</div>
                        @endif

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled" @disabled(! $importEnabled)>
                                <span wire:loading.remove wire:target="analyse">Analyser l annonce</span>
                                <span wire:loading wire:target="analyse">Analyse en cours...</span>
                            </button>
                            <button type="button" wire:click="startManual" class="btn-secondary">Saisir manuellement</button>
                        </div>
                    </form>
                @else
                    @if ($preview && count($preview['duplicates']))
                        <div class="alert-box border-amber-300 bg-amber-50 text-amber-900">
                            <p class="font-semibold">Annonce potentiellement existante</p>
                            <ul class="mt-2 space-y-1">
                                @foreach ($preview['duplicates'] as $duplicate)
                                    <li class="flex flex-wrap items-center gap-2">
                                        <span>{{ $duplicate->brand }} {{ $duplicate->model }} {{ $duplicate->year }} —
                                            {{ number_format($duplicate->price, 0, ',', ' ') }} FCFA</span>
                                        <span class="badge {{ $duplicate->status->badgeClass() }}">{{ $duplicate->status->label() }}</span>
                                        <a href="{{ route('admin.vehicles.edit', $duplicate) }}" class="text-xs font-medium underline">
                                            Ouvrir l annonce existante
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="mt-3 flex gap-3">
                                <button type="button" wire:click="$set('step', 'url')" class="btn-secondary btn-sm">Annuler</button>
                            </div>
                        </div>
                    @endif

                    @if ($preview && count($preview['warnings']))
                        <div class="alert-box border-ink-200 bg-white text-ink-700">
                            <p class="font-semibold">Avertissements</p>
                            <ul class="mt-1 list-inside list-disc text-xs">
                                @foreach ($preview['warnings'] as $warning)
                                    <li>{{ $warning }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form wire:submit="create" class="card space-y-5 p-6">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold text-ink-900">
                                Correction avant creation
                                @if ($preview)
                                    <span class="badge ml-2 {{ $preview['extraction'] === 'AUTO' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                        Extraction {{ $preview['extraction'] === 'AUTO' ? 'automatique' : ($preview['extraction'] === 'PARTIAL' ? 'partielle' : 'nulle') }}
                                    </span>
                                @endif
                            </h2>
                            <button type="button" wire:click="$set('step', 'url')" class="text-xs text-ink-500 hover:text-brand-700">
                                Changer d URL
                            </button>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label" for="f-brand">Marque *</label>
                                <input id="f-brand" type="text" wire:model="form.brand" class="input @error('form.brand') border-red-400 @enderror">
                                @error('form.brand') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label" for="f-model">Modele *</label>
                                <input id="f-model" type="text" wire:model="form.model" class="input @error('form.model') border-red-400 @enderror">
                                @error('form.model') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="label" for="f-year">Annee *</label>
                                <input id="f-year" type="number" wire:model="form.year" class="input @error('form.year') border-red-400 @enderror">
                                @error('form.year') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label" for="f-price">Prix (FCFA) *</label>
                                <input id="f-price" type="number" wire:model="form.price" class="input @error('form.price') border-red-400 @enderror">
                                @error('form.price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label" for="f-mileage">Kilometrage</label>
                                <input id="f-mileage" type="number" wire:model="form.mileage" class="input">
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="label" for="f-fuel">Carburant</label>
                                <select id="f-fuel" wire:model="form.fuel" class="input">
                                    <option value="">Non renseigne</option>
                                    @foreach ($fuelOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label" for="f-transmission">Boite</label>
                                <select id="f-transmission" wire:model="form.transmission" class="input">
                                    <option value="">Non renseigne</option>
                                    @foreach ($transmissionOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label" for="f-body">Carrosserie</label>
                                <select id="f-body" wire:model="form.body_type" class="input">
                                    <option value="">Non renseigne</option>
                                    @foreach ($bodyTypeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label" for="f-location">Localisation</label>
                                <input id="f-location" type="text" wire:model="form.location" class="input">
                            </div>
                            <div>
                                <label class="label" for="f-color">Couleur</label>
                                <input id="f-color" type="text" wire:model="form.color" class="input">
                            </div>
                        </div>

                        <div>
                            <label class="label" for="f-source">Source reference</label>
                            <select id="f-source" wire:model="sourceId" class="input">
                                <option value="">Aucune (detection automatique)</option>
                                @foreach ($sources as $source)
                                    <option value="{{ $source->id }}">{{ $source->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <label class="flex items-center gap-3">
                            <input type="checkbox" wire:model="publishNow" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-ink-700">Publier immediatement (declenche les alertes correspondantes)</span>
                        </label>

                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="create">Creer l annonce</span>
                            <span wire:loading wire:target="create">Creation en cours...</span>
                        </button>
                    </form>
                @endif
            </div>

            <aside class="lg:sticky lg:top-24 lg:h-fit">
                <div class="card p-5">
                    <h2 class="font-semibold text-ink-900">Regles de l import</h2>
                    <ul class="mt-3 list-inside list-disc space-y-2 text-sm text-ink-600">
                        <li>Le systeme lit les balises <code>og:</code>, le JSON-LD et les micro-donnees de la page.</li>
                        <li><code>robots.txt</code> est verifie : une source qui interdit l extraction n est pas lue.</li>
                        <li>Seuls les sites autorises dans <code>IMPORT_ALLOWED_HOSTS</code> peuvent etre importes.</li>
                        <li>Les adresses internes (localhost, reseau prive) sont bloquees.</li>
                        <li>La reponse est limitee en taille et en duree.</li>
                        <li>Les images importees sont des liens distants : verifiez les droits avant publication.</li>
                    </ul>

                    <div class="mt-4 rounded-xl bg-ink-50 p-3 text-xs text-ink-600">
                        En cas d echec d extraction, utilisez la saisie manuelle : l application fonctionne independamment
                        de l import automatique.
                    </div>
                </div>
            </aside>
        </div>
</div>
