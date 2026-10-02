<div>
        <div class="container-app py-10">
            <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-ink-900">Mes notifications</h1>
                    <p class="mt-1 text-sm text-ink-500">
                        {{ $unreadCount }} non lue{{ $unreadCount > 1 ? 's' : '' }}.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select wire:model.live="channel" class="input w-auto" aria-label="Filtrer par canal">
                        <option value="">Tous les canaux</option>
                        @foreach ($channelOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <label class="flex items-center gap-2 text-sm text-ink-600">
                        <input type="checkbox" wire:model.live="unreadOnly" class="rounded border-ink-300 text-brand-600">
                        Non lues seulement
                    </label>

                    <button type="button" wire:click="markAllRead" class="btn-secondary btn-sm">Tout marquer comme lu</button>
                </div>
            </header>

            @if ($notifications->isEmpty())
                <x-empty-state title="Aucune notification"
                               description="Vous serez prevenu ici des qu un vehicule correspond a l une de vos alertes.">
                    <a href="{{ route('alerts.create') }}" class="btn-primary mt-2">Creer une alerte</a>
                </x-empty-state>
            @else
                <ul class="space-y-3">
                    @foreach ($notifications as $notification)
                        @php $vehicle = $notification->vehicle; @endphp
                        <li wire:key="notif-{{ $notification->id }}"
                            class="card flex gap-4 p-4 {{ $notification->read_at ? '' : 'border-brand-200 bg-brand-50/40' }}">
                            <a href="{{ route('vehicles.show', $vehicle) }}" class="h-24 w-32 shrink-0 overflow-hidden rounded-xl bg-ink-100">
                                @if ($vehicle->mainImage)
                                    <img src="{{ $vehicle->mainImage->url }}" alt="" class="h-full w-full object-cover">
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge border-ink-200 bg-white text-ink-600">{{ $notification->channel->label() }}</span>
                                    @if (! $notification->read_at)
                                        <span class="badge border-brand-200 bg-brand-50 text-brand-700">Nouveau</span>
                                    @endif
                                    @if ($notification->status->value === 'FAILED')
                                        <span class="badge border-red-200 bg-red-50 text-red-700">Echec d envoi</span>
                                    @endif
                                    <span class="text-xs text-ink-400">{{ $notification->created_at->diffForHumans() }}</span>
                                </div>

                                <p class="mt-1.5 font-medium text-ink-900">
                                    @if ($notification->subject)
                                        {{ $notification->subject }}
                                    @else
                                        {{ $vehicle->brand }} {{ $vehicle->model }} {{ $vehicle->year }}
                                    @endif
                                    <span class="ml-2 text-brand-700">{{ number_format($vehicle->price, 0, ',', ' ') }} FCFA</span>
                                </p>

                                <p class="mt-0.5 text-xs text-ink-500">
                                    {{ number_format($vehicle->mileage, 0, ',', ' ') }} km ·
                                    {{ $vehicle->transmission->label() }} ·
                                    {{ $vehicle->fuel->label() }}
                                    @if ($notification->alert)
                                        · Alerte : {{ $notification->alert->name }}
                                    @endif
                                </p>

                                @if ($notification->error)
                                    <p class="mt-2 text-xs text-red-600">Erreur : {{ $notification->error }}</p>
                                @endif
                            </div>

                            <div class="flex shrink-0 flex-col items-end justify-between gap-2">
                                <a href="{{ route('vehicles.show', $vehicle) }}" class="btn-primary btn-sm">Voir</a>
                                @unless ($notification->read_at)
                                    <button type="button" wire:click="markRead({{ $notification->id }})" class="text-xs text-ink-500 hover:text-brand-700">
                                        Marquer comme lu
                                    </button>
                                @endunless
                                <button type="button" wire:click="delete({{ $notification->id }})"
                                        wire:confirm="Supprimer cette notification ?" class="text-xs text-ink-400 hover:text-red-600">
                                    Supprimer
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-10">{{ $notifications->links() }}</div>
            @endif
        </div>

        <x-toast-host />
</div>
