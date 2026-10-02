<div>
        <div class="container-app py-10">
            <header class="mb-6">
                <h1 class="text-2xl font-bold tracking-tight text-ink-900">
                    {{ $alert ? 'Modifier mon alerte' : 'Creer une alerte' }}
                </h1>
                <p class="mt-1 text-sm text-ink-500">
                    Decrivez ce que vous cherchez : des qu une annonce correspond, vous etes prevenu.
                </p>
            </header>

            @if (request('vehicule'))
                <div class="alert-box mb-6 border-brand-200 bg-brand-50 text-brand-800">
                    Astuce : nous avons pre-rempli les criteres avec le vehicule que vous consultez.
                </div>
            @endif

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
                <form wire:submit="save" class="card space-y-6 p-6">
                    <div>
                        <label class="label" for="name">Nom de l alerte</label>
                        <input id="name" type="text" wire:model="name" class="input @error('name') border-red-400 @enderror"
                               placeholder="Ex : Toyota RAV4 automatique" maxlength="80">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <fieldset class="space-y-4">
                        <legend class="text-sm font-semibold text-ink-900">Le vehicule</legend>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label" for="brand">Marque</label>
                                <input id="brand" type="text" wire:model="brand" class="input" placeholder="Toyota">
                            </div>
                            <div>
                                <label class="label" for="model">Modele</label>
                                <input id="model" type="text" wire:model="model" class="input" placeholder="RAV4">
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="label" for="minYear">Annee minimum</label>
                                <input id="minYear" type="number" wire:model="minYear" class="input" placeholder="2021" min="1900">
                            </div>
                            <div>
                                <label class="label" for="maxYear">Annee maximum</label>
                                <input id="maxYear" type="number" wire:model="maxYear" class="input" placeholder="{{ date('Y') }}" min="1900">
                            </div>
                            <div>
                                <label class="label" for="bodyType">Carrosserie</label>
                                <select id="bodyType" wire:model="bodyType" class="input">
                                    <option value="">Toutes</option>
                                    @foreach ($bodyTypeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4">
                        <legend class="text-sm font-semibold text-ink-900">Le budget et l usage</legend>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="label" for="minPrice">Prix minimum (FCFA)</label>
                                <input id="minPrice" type="number" wire:model="minPrice" class="input" placeholder="0" min="0">
                                @error('minPrice') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label" for="maxPrice">Prix maximum (FCFA)</label>
                                <input id="maxPrice" type="number" wire:model="maxPrice" class="input" placeholder="20000000" min="0">
                                @error('maxPrice') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label" for="maxMileage">Kilometrage max</label>
                                <input id="maxMileage" type="number" wire:model="maxMileage" class="input" placeholder="100000" min="0">
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="label" for="fuel">Carburant</label>
                                <select id="fuel" wire:model="fuel" class="input">
                                    <option value="">Tous</option>
                                    @foreach ($fuelOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label" for="transmission">Boite de vitesse</label>
                                <select id="transmission" wire:model="transmission" class="input">
                                    <option value="">Toutes</option>
                                    @foreach ($transmissionOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label" for="location">Localisation</label>
                                <input id="location" type="text" wire:model="location" class="input" placeholder="Dakar">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="label">Frequence de notification</legend>
                        <div class="grid gap-3 sm:grid-cols-3">
                            @foreach ($frequencyOptions as $value => $label)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition
                                            {{ $frequency === $value ? 'border-brand-500 bg-brand-50' : 'border-ink-200 hover:border-ink-300' }}">
                                    <input type="radio" wire:model="frequency" value="{{ $value }}" class="mt-0.5">
                                    <span>
                                        <span class="block text-sm font-medium text-ink-900">{{ $label }}</span>
                                        <span class="block text-xs text-ink-500">{{ \App\Enums\AlertFrequency::from($value)->description() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" wire:model="isActive" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm text-ink-700">Activer immediatement cette alerte</span>
                    </label>

                    <div class="flex flex-wrap gap-3 border-t border-ink-100 pt-6">
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">{{ $alert ? 'Enregistrer les modifications' : 'Creer l alerte' }}</span>
                            <span wire:loading wire:target="save">Enregistrement...</span>
                        </button>
                        <a href="{{ route('alerts.index') }}" class="btn-secondary">Annuler</a>
                    </div>
                </form>

                <aside class="lg:sticky lg:top-24 lg:h-fit">
                    <div class="card p-5">
                        <h2 class="font-semibold text-ink-900">Correspondances actuelles</h2>
                        <p class="mt-1 text-xs text-ink-500">Mettez a jour les criteres pour voir la liste evoluer.</p>

                        <div wire:key="preview-{{ count($this->preview) }}" class="mt-4 space-y-3">
                            @forelse ($this->preview as $item)
                                <a href="{{ route('vehicles.show', $item) }}" wire:key="preview-{{ $item->id }}"
                                   class="flex gap-3 rounded-xl border border-ink-200 p-2 hover:border-brand-300">
                                    <span class="h-14 w-20 shrink-0 overflow-hidden rounded-lg bg-ink-100">
                                        @if ($item->mainImage)
                                            <img src="{{ $item->mainImage->url }}" alt="" class="h-full w-full object-cover">
                                        @endif
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-ink-900">{{ $item->title() }}</span>
                                        <span class="block text-xs text-brand-700">{{ number_format($item->price, 0, ',', ' ') }} FCFA</span>
                                    </span>
                                </a>
                            @empty
                                <p class="rounded-xl bg-ink-50 p-4 text-center text-sm text-ink-500">
                                    Aucun vehicule ne correspond encore a ces criteres.
                                </p>
                            @endforelse
                        </div>

                        @auth
                            <div class="mt-4 border-t border-ink-100 pt-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-ink-400">Canaux de notification</p>
                                <ul class="mt-2 space-y-1 text-sm text-ink-600">
                                    @foreach ($channelOptions as $channel => $label)
                                        @php $enabled = match($channel) {
                                            'EMAIL' => auth()->user()->notify_email,
                                            'WHATSAPP' => auth()->user()->notify_whatsapp,
                                            default => auth()->user()->notify_in_app,
                                        }; @endphp
                                        <li class="flex items-center justify-between">
                                            <span>{{ $label }}</span>
                                            <span class="badge {{ $enabled ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-ink-200 bg-ink-100 text-ink-400' }}">
                                                {{ $enabled ? 'Actif' : 'Inactif' }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('preferences.edit') }}" class="mt-3 inline-block text-xs font-medium text-brand-700 hover:underline">
                                    Modifier mes preferences
                                </a>
                            </div>
                        @endauth
                    </div>
                </aside>
            </div>
        </div>

        <x-toast-host />
</div>
