<div>
        <div class="container-app py-10">
            <header class="mb-6">
                <h1 class="text-2xl font-bold tracking-tight text-ink-900">Preferences de notification</h1>
                <p class="mt-1 text-sm text-ink-500">Choisissez comment et quand vous souhaitez etre prevenu.</p>
            </header>

            <form wire:submit="save" class="max-w-2xl space-y-6">
                <div class="card p-6">
                    <h2 class="font-semibold text-ink-900">Canaux</h2>
                    <p class="mt-1 text-sm text-ink-500">Chaque canal peut etre active independamment.</p>

                    <div class="mt-5 space-y-4">
                        <label class="flex items-start gap-3 rounded-xl border border-ink-200 p-4">
                            <input type="checkbox" wire:model="notifyEmail" class="mt-1 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <span class="block text-sm font-medium text-ink-900">Recevoir par email</span>
                                <span class="block text-xs text-ink-500">{{ $user->email }} — notification immediate ou resume selon la frequence de chaque alerte.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-xl border border-ink-200 p-4">
                            <input type="checkbox" wire:model="notifyWhatsapp" class="mt-1 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <span class="block text-sm font-medium text-ink-900">Recevoir par WhatsApp</span>
                                <span class="block text-xs text-ink-500">
                                    {{ $user->phone
                                        ? 'Numero enregistre : '.$user->phone
                                        : 'Renseignez votre numero de telephone dans votre profil pour activer ce canal.' }}
                                </span>
                            </span>
                        </label>
                        @error('notifyWhatsapp') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                        <label class="flex items-start gap-3 rounded-xl border border-ink-200 p-4">
                            <input type="checkbox" wire:model="notifyInApp" class="mt-1 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <span class="block text-sm font-medium text-ink-900">Notifications sur le site</span>
                                <span class="block text-xs text-ink-500">Visible dans votre tableau de bord, toujours gratuit.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="card p-6">
                    <h2 class="font-semibold text-ink-900">Frequence par defaut</h2>
                    <p class="mt-1 text-sm text-ink-500">Utilisee pour les nouvelles alertes ; modifiable alert par alerte.</p>

                    <div class="mt-4 space-y-2">
                        @foreach ($frequencyOptions as $value => $label)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3
                                        {{ $defaultFrequency === $value ? 'border-brand-500 bg-brand-50' : 'border-ink-200' }}">
                                <input type="radio" wire:model="defaultFrequency" value="{{ $value }}">
                                <span>
                                    <span class="block text-sm font-medium text-ink-900">{{ $label }}</span>
                                    <span class="block text-xs text-ink-500">{{ \App\Enums\AlertFrequency::from($value)->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">Enregistrer</button>
                    <a href="{{ route('profile.edit') }}" class="btn-secondary">Mon profil</a>
                </div>
            </form>
        </div>

        <div x-data="{ show: false }" x-on:preferences-saved.window="show = true; setTimeout(() => show = false, 4000)">
            <template x-if="show">
                <div class="pointer-events-none fixed inset-x-0 top-20 z-50 flex justify-center px-4">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg">
                        Preferences mises a jour.
                    </div>
                </div>
            </template>
        </div>
</div>
