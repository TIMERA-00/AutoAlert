<div>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink-900">Utilisateurs</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $users->total() }} compte(s).</p>
            </div>

            <select wire:model.live="role" class="input w-auto" aria-label="Filtrer par role">
                <option value="">Tous les roles</option>
                @foreach ($roleOptions as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="card p-4">
            <input type="search" wire:model.live.debounce.400ms="search" class="input"
                   placeholder="Rechercher par nom, email, telephone..." aria-label="Rechercher un utilisateur">
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="overflow-x-auto rounded-2xl border border-ink-200 bg-white">
                <table class="min-w-full divide-y divide-ink-200 text-sm">
                    <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Utilisateur</th>
                            <th class="hidden px-4 py-3 font-semibold sm:table-cell">Alertes / Favoris</th>
                            <th class="px-4 py-3 font-semibold">Inscription</th>
                            <th class="px-4 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($users as $user)
                            <tr wire:key="user-{{ $user->id }}" class="hover:bg-ink-50/60">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">
                                            {{ $user->initials }}
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block font-medium text-ink-900">
                                                {{ $user->fullName() }}
                                                @if ($user->isAdmin())
                                                    <span class="badge ml-1 border-brand-200 bg-brand-50 text-brand-700">Admin</span>
                                                @endif
                                                @unless ($user->is_active)
                                                    <span class="badge ml-1 border-red-200 bg-red-50 text-red-700">Desactive</span>
                                                @endunless
                                            </span>
                                            <span class="block truncate text-xs text-ink-500">{{ $user->email }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="hidden px-4 py-3 text-ink-600 sm:table-cell">
                                    {{ $user->alerts_count }} / {{ $user->favorites_count }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-ink-600">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-1.5">
                                        <button type="button" wire:click="showDetail({{ $user->id }})" class="btn-secondary btn-sm">Fiche</button>
                                        <button type="button" wire:click="makeAdmin({{ $user->id }})" class="btn-secondary btn-sm">
                                            {{ $user->isAdmin() ? 'Retirer admin' : 'Passer admin' }}
                                        </button>
                                        <button type="button" wire:click="toggleActive({{ $user->id }})" class="btn-secondary btn-sm">
                                            {{ $user->is_active ? 'Desactiver' : 'Reactiver' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-12 text-center text-ink-500">Aucun utilisateur trouve.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="px-4 py-4">{{ $users->links() }}</div>
            </div>

            <aside class="lg:h-fit">
                @if ($detail)
                    <div class="card p-5" wire:key="detail-{{ $detail->id }}">
                        <div class="flex items-center gap-3">
                            <span class="grid h-12 w-12 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                                {{ $detail->initials }}
                            </span>
                            <div>
                                <h2 class="font-semibold text-ink-900">{{ $detail->fullName() }}</h2>
                                <p class="text-xs text-ink-500">{{ $detail->email }}</p>
                                <p class="text-xs text-ink-500">{{ $detail->phone ?: 'Aucun telephone' }}</p>
                            </div>
                        </div>

                        <dl class="mt-5 space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-ink-500">Role</dt><dd>{{ $detail->role->label() }}</dd></div>
                            <div class="flex justify-between"><dt class="text-ink-500">Inscription</dt><dd>{{ $detail->created_at->translatedFormat('d M Y') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-ink-500">Derniere connexion</dt><dd>{{ $detail->last_login_at?->diffForHumans() ?? 'jamais' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-ink-500">Email</dt><dd>{{ $detail->notify_email ? 'actif' : 'desactive' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-ink-500">WhatsApp</dt><dd>{{ $detail->notify_whatsapp ? 'actif' : 'desactive' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-ink-500">Notifications site</dt><dd>{{ $detail->notify_in_app ? 'actif' : 'desactive' }}</dd></div>
                        </dl>

                        <h3 class="mt-5 text-sm font-semibold text-ink-900">Alertes ({{ $detail->alerts->count() }})</h3>
                        <ul class="mt-2 space-y-2 text-sm">
                            @forelse ($detail->alerts as $alert)
                                <li class="rounded-lg border border-ink-200 p-2">
                                    <span class="block font-medium text-ink-800">{{ $alert->name }}</span>
                                    <span class="text-xs text-ink-500">{{ $alert->summary() }}</span>
                                </li>
                            @empty
                                <li class="text-xs text-ink-500">Aucune alerte.</li>
                            @endforelse
                        </ul>

                        <h3 class="mt-5 text-sm font-semibold text-ink-900">Dernieres notifications</h3>
                        <ul class="mt-2 space-y-2 text-sm">
                            @forelse ($detail->notifications as $notification)
                                <li class="rounded-lg border border-ink-200 p-2">
                                    <span class="block text-ink-800">{{ $notification->vehicle->title() }}</span>
                                    <span class="text-xs text-ink-500">
                                        {{ $notification->channel->label() }} · {{ $notification->status->label() }} ·
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </li>
                            @empty
                                <li class="text-xs text-ink-500">Aucune notification.</li>
                            @endforelse
                        </ul>
                    </div>
                @else
                    <div class="card p-6 text-center text-sm text-ink-500">
                        Selectionnez un utilisateur pour consulter ses alertes et ses preferences de notification.
                    </div>
                @endif
            </aside>
        </div>

        <x-toast-host />
</div>
