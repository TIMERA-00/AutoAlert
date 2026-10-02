<div>
        <div class="container-app py-10">
            <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-ink-900">Mes alertes</h1>
                    <p class="mt-1 text-sm text-ink-500">
                        {{ $alerts->where('is_active', true)->count() }} active{{ $alerts->where('is_active', true)->count() > 1 ? 's' : '' }}
                        sur {{ $alerts->count() }}.
                    </p>
                </div>

                <a href="{{ route('alerts.create') }}" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Creer une alerte
                </a>
            </header>

            <input type="search" wire:model.live.debounce.400ms="search" placeholder="Rechercher une alerte..."
                   class="input mb-6 max-w-sm" aria-label="Rechercher une alerte">

            @if ($alerts->isEmpty())
                <x-empty-state title="Vous n avez pas encore d alerte"
                               description="Une alerte definit vos criteres : nous surveillons les publications et vous prevenons.">
                    <a href="{{ route('alerts.create') }}" class="btn-primary mt-2">Creer ma premiere alerte</a>
                </x-empty-state>
            @else
                <ul class="space-y-4">
                    @foreach ($alerts as $alert)
                        <li wire:key="alert-{{ $alert->id }}" class="card p-5">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-semibold text-ink-900">{{ $alert->name }}</h2>
                                        <span class="badge {{ $alert->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-ink-200 bg-ink-100 text-ink-500' }}">
                                            {{ $alert->is_active ? 'Active' : 'Desactivee' }}
                                        </span>
                                        <span class="badge border-brand-200 bg-brand-50 text-brand-700">{{ $alert->frequency->label() }}</span>
                                    </div>

                                    <p class="mt-1.5 text-sm text-ink-600">{{ $alert->summary() }}</p>

                                    <p class="mt-2 text-xs text-ink-500">
                                        @if ($matchCounts[$alert->id] > 0)
                                            <span class="font-medium text-emerald-700">{{ $matchCounts[$alert->id] }} vehicule(s) correspondent actuellement</span>
                                        @else
                                            Aucun vehicule ne correspond pour le moment.
                                        @endif
                                        @if ($alert->last_notified_at)
                                            · derniere notification {{ $alert->last_notified_at->diffForHumans() }}
                                        @endif
                                        · {{ $alert->notifications()->count() }} notification(s)
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" wire:click="toggle({{ $alert->id }})" class="btn-secondary btn-sm">
                                        {{ $alert->is_active ? 'Desactiver' : 'Activer' }}
                                    </button>
                                    <a href="{{ route('alerts.edit', $alert) }}" class="btn-secondary btn-sm">Modifier</a>
                                    <button type="button" wire:click="delete({{ $alert->id }})"
                                            wire:confirm="Supprimer definitivement cette alerte ?"
                                            class="btn-danger btn-sm">Supprimer</button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <x-toast-host />
</div>
