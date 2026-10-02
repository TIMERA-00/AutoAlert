<div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-ink-900">Notifications</h1>
            <p class="mt-1 text-sm text-ink-500">Journal des envois par canal, avec relance manuelle des echecs.</p>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($summary as $row)
                <div class="card px-4 py-3">
                    <span class="block text-xs uppercase tracking-wide text-ink-500">{{ $row->channel }} · {{ $row->status }}</span>
                    <span class="mt-1 block text-xl font-bold text-ink-900">{{ $row->total }}</span>
                </div>
            @endforeach
        </div>

        <div class="card p-4">
            <div class="flex flex-col gap-3 sm:flex-row">
                <select wire:model.live="channel" class="input sm:w-56" aria-label="Filtrer par canal">
                    <option value="">Tous les canaux</option>
                    @foreach ($channelOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select wire:model.live="status" class="input sm:w-56" aria-label="Filtrer par statut">
                    <option value="">Tous les statuts</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-ink-200 bg-white">
            <table class="min-w-full divide-y divide-ink-200 text-sm">
                <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Destinataire</th>
                        <th class="px-4 py-3 font-semibold">Vehicule</th>
                        <th class="px-4 py-3 font-semibold">Canal</th>
                        <th class="px-4 py-3 font-semibold">Statut</th>
                        <th class="px-4 py-3 font-semibold">Date</th>
                        <th class="px-4 py-3 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($notifications as $notification)
                        <tr wire:key="notif-{{ $notification->id }}" class="hover:bg-ink-50/60">
                            <td class="px-4 py-3">
                                <span class="block text-ink-800">{{ $notification->user?->fullName() ?? '—' }}</span>
                                <span class="block text-xs text-ink-500">{{ $notification->user?->email }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('vehicles.show', $notification->vehicle) }}" class="text-brand-700 hover:underline">
                                    {{ $notification->vehicle->title() }}
                                </a>
                                @if ($notification->alert)
                                    <span class="block text-xs text-ink-500">alerte : {{ $notification->alert->name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink-600">{{ $notification->channel->label() }}</td>
                            <td class="px-4 py-3">
                                <span class="badge {{ match ($notification->status->value) {
                                    'SENT', 'READ' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                    'FAILED' => 'border-red-200 bg-red-50 text-red-700',
                                    default => 'border-ink-200 bg-ink-100 text-ink-600',
                                } }}">{{ $notification->status->label() }}</span>
                                @if ($notification->error)
                                    <span class="mt-1 block max-w-xs truncate text-xs text-red-600" title="{{ $notification->error }}">
                                        {{ $notification->error }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-500">{{ $notification->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($notification->status->value === 'FAILED' && $notification->channel->value !== 'IN_APP')
                                    <button type="button" wire:click="retry({{ $notification->id }})" class="btn-secondary btn-sm">Relancer</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-ink-500">Aucune notification envoyee.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>

        <x-toast-host />
</div>
