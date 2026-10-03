@php
    use App\Enums\ChatRole;
@endphp

<div
    class="pointer-events-none fixed bottom-4 right-4 z-50 flex flex-col items-end gap-3 sm:bottom-6 sm:right-6"
    x-data="assistantWidget({ open: @js($this->isOpen()) })"
    @assistant-updated.window="$nextTick(() => scrollToBottom())"
    x-on:keydown.escape.window="open = false"
>
    {{-- Launcher --}}
    <button
        type="button"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open"
        aria-controls="assistant-panel"
        class="pointer-events-auto flex items-center gap-2 rounded-full bg-ink-900 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-ink-900/20 transition hover:bg-ink-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-3.03 8.25-6.75 8.25a6.7 6.7 0 0 1-3-.7L3 21l1.45-4.2A6.7 6.7 0 0 1 4.5 12c0-4.556 3.03-8.25 6.75-8.25S18 7.444 18 12Z"/>
        </svg>
        <span x-show="! open" x-cloak>Assistant</span>
        <span x-show="open" x-cloak class="sr-only">Fermer l'assistant</span>
    </button>

    {{-- Panel --}}
    <section
        id="assistant-panel"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="pointer-events-auto flex h-[min(38rem,calc(100vh-7rem))] w-[min(24rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-2xl"
        role="dialog"
        aria-label="Assistant AutoAlert"
    >
        {{-- Header --}}
        <header class="flex items-center justify-between gap-3 border-b border-ink-100 bg-ink-50 px-4 py-3">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-600 text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ink-900">Assistant AutoAlert</p>
                    <p class="truncate text-xs text-ink-500">
                        @if ($this->engine() === 'openai')
                            Analyse d'images et conversation
                        @else
                            Recherche dans le catalogue
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <button
                    type="button"
                    x-on:click="speakLastReply()"
                    x-bind:class="voiceReply ? 'bg-brand-100 text-brand-700' : 'text-ink-500 hover:bg-ink-100'"
                    class="rounded-lg p-1.5 transition"
                    title="Lire les reponses a voix haute"
                    aria-label="Lire les reponses a voix haute"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V5.25a3 3 0 0 1 6 0v7.5a3 3 0 0 1-3 3Z"/>
                    </svg>
                </button>

                <button
                    type="button"
                    wire:click="restart"
                    wire:loading.attr="disabled"
                    class="rounded-lg p-1.5 text-ink-500 transition hover:bg-ink-100"
                    title="Nouvelle conversation"
                    aria-label="Nouvelle conversation"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                </button>

                <button
                    type="button"
                    x-on:click="open = false"
                    class="rounded-lg p-1.5 text-ink-500 transition hover:bg-ink-100"
                    aria-label="Fermer"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </header>

        {{-- Voice state --}}
        <div
            x-show="listening"
            x-cloak
            class="flex items-center gap-2 border-b border-brand-100 bg-brand-50 px-4 py-2 text-xs text-brand-800"
            role="status"
        >
            <span class="flex items-end gap-0.5" aria-hidden="true">
                @foreach ([8, 16, 24, 12] as $height)
                    <span class="w-1 animate-pulse rounded-full bg-brand-600" style="height: {{ $height }}px"></span>
                @endforeach
            </span>
            <span class="truncate" x-text="interim || 'Je vous ecoute...'"></span>
        </div>

        {{-- Messages --}}
        <div
            class="flex-1 space-y-4 overflow-y-auto px-4 py-4"
            x-ref="scroller"
            x-init="$nextTick(() => scrollToBottom())"
        >
            @if ($this->transcript()->isEmpty())
                <div class="rounded-xl bg-ink-50 p-4 text-sm text-ink-600">
                    <p class="font-medium text-ink-900">Que voulez-vous chercher ?</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($this->starters() as $key => $starter)
                            @continue($key === 'hints')
                            <li>
                                <button
                                    type="button"
                                    wire:click="useStarter(@js($starter))"
                                    class="flex w-full items-center gap-2 rounded-lg border border-ink-200 bg-white px-3 py-2 text-left text-sm text-ink-700 transition hover:border-brand-300 hover:bg-brand-50"
                                >
                                    <svg class="h-4 w-4 shrink-0 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-3.03 8.25-6.75 8.25a6.7 6.7 0 0 1-3-.7L3 21l1.45-4.2A6.7 6.7 0 0 1 4.5 12c0-4.556 3.03-8.25 6.75-8.25S18 7.444 18 12Z"/>
                                    </svg>
                                    {{ $starter }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-xs text-ink-400">{{ $this->starters()['hints'] ?? '' }}</p>
                </div>
            @endif

            @foreach ($this->transcript() as $chatMessage)
                @php
                    $isUser = $chatMessage->role === ChatRole::User;
                    $draft = $chatMessage->alertDraft();
                    $cards = $this->vehiclesFor($chatMessage);
                    $identification = $chatMessage->attachments['identification'] ?? null;
                @endphp

                <div wire:key="msg-{{ $chatMessage->id }}" class="flex flex-col gap-2 {{ $isUser ? 'items-end' : 'items-start' }}">
                    @if ($isUser)
                        @foreach ($chatMessage->photos() as $photo)
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::url($photo['url']) }}"
                                alt="Photo envoyee"
                                class="h-28 w-36 rounded-xl border border-ink-200 object-cover"
                            >
                        @endforeach
                    @endif

                    <div
                        data-assistant-message="{{ $isUser ? '' : $chatMessage->content }}"
                        @class([
                            'max-w-[85%] rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed whitespace-pre-line',
                            'bg-brand-600 text-white' => $isUser,
                            'bg-ink-100 text-ink-800' => ! $isUser,
                        ])
                    >
                        {{ $chatMessage->content }}
                    </div>

                    @if (! $isUser && $identification)
                        <div class="w-full max-w-[85%] rounded-xl border border-brand-200 bg-brand-50/60 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Photo analysee</p>
                            <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-ink-700">
                                @foreach (['Marque' => $identification['brand'] ?? null, 'Modele' => $identification['model'] ?? null, 'Annee estimee' => $identification['year'] ?? null, 'Carrosserie' => $identification['body_type'] ?? null] as $label => $value)
                                    @continue(! $value)
                                    <dt class="text-ink-500">{{ $label }}</dt>
                                    <dd class="truncate font-medium">{{ $value }}</dd>
                                @endforeach
                            </dl>
                            <p class="mt-2 text-xs text-ink-500">
                                Confiance : {{ round(($identification['confidence'] ?? 0) * 100) }}%
                                @if (($identification['source'] ?? null) === 'unavailable')
                                    <span class="block text-ink-400">Cle OPENAI_API_KEY absente : reconnaissance limitee.</span>
                                @endif
                            </p>
                        </div>
                    @endif

                    @if ($cards)
                        <div class="w-full max-w-[85%] space-y-2">
                            @foreach ($cards as $card)
                                <a
                                    href="{{ $card['url'] }}"
                                    class="flex gap-3 rounded-xl border border-ink-200 bg-white p-2 transition hover:border-brand-300 hover:shadow-sm"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    @if ($card['image'])
                                        <img src="{{ $card['image'] }}" alt="" class="h-14 w-20 shrink-0 rounded-lg object-cover">
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-ink-900">{{ $card['title'] }}</p>
                                        <p class="truncate text-xs text-brand-700">{{ $card['price'] }}</p>
                                        <p class="truncate text-xs text-ink-500">{{ $card['meta'] }}</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if ($draft)
                        <div
                            wire:key="draft-{{ $chatMessage->id }}"
                            class="w-full max-w-[85%] rounded-xl border border-brand-200 bg-white p-3"
                        >
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Creer cette alerte</p>
                            <p class="mt-1 text-sm font-medium text-ink-900">{{ $draft['name'] }}</p>
                            <p class="text-xs text-ink-500">
                                {{ ($draft['match_count'] ?? 0) > 0
                                    ? ($draft['match_count'] == 1 ? '1 vehicule correspond' : $draft['match_count'].' vehicules correspondent').' aujourd hui.'
                                    : 'Aucun resultat pour l instant, mais vous serez prevenu des le premier.' }}
                            </p>

                            <div class="mt-2 flex gap-2">
                                <button
                                    type="button"
                                    wire:click="acceptDraft({{ $chatMessage->id }})"
                                    wire:loading.attr="disabled"
                                    class="btn-primary btn-sm flex-1"
                                >
                                    @if ($this->userIsAuthenticated())
                                        Enregistrer
                                    @else
                                        Se connecter pour valider
                                    @endif
                                </button>
                                <button
                                    type="button"
                                    wire:click="dismissDraft"
                                    class="btn-ghost btn-sm"
                                >
                                    Non merci
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            <div wire:loading.flex wire:target="send" class="items-center gap-2 text-xs text-ink-500">
                <span class="h-2 w-2 animate-ping rounded-full bg-brand-500"></span>
                L assistant cherche...
            </div>
        </div>

        {{-- Composer --}}
        <div class="border-t border-ink-100 bg-white p-3">
            @if ($notice)
                <p class="mb-2 rounded-lg bg-brand-50 px-3 py-2 text-xs text-brand-800" role="status">{{ $notice }}</p>
            @endif

            @error('photo')
                <p class="mb-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700" role="alert">{{ $message }}</p>
            @enderror

            @if ($this->photo)
                <div class="mb-2 flex items-center gap-2">
                    <img src="{{ $this->photo->temporaryUrl() }}" alt="Apercu" class="h-14 w-20 rounded-lg object-cover">
                    <button type="button" wire:click="removePhoto" class="text-xs text-ink-500 underline hover:text-red-600">
                        Retirer
                    </button>
                </div>
            @endif

            <form
                wire:submit="send"
                class="flex items-end gap-1.5"
                x-ref="form"
            >
                <div class="relative flex-1">
                    <label for="assistant-text" class="sr-only">Votre message</label>
                    <textarea
                        id="assistant-text"
                        wire:model="message"
                        rows="1"
                        x-model="speech"
                        x-on:input="autoGrow($event.target)"
                        x-on:keydown.enter.prevent="if (! shiftKey) $refs.form.requestSubmit()"
                        placeholder="Ecrivez ou parlez..."
                        class="input max-h-32 min-h-[2.5rem] w-full resize-none py-2.5 pr-10 text-sm"
                        x-bind:placeholder="listening ? 'Je vous ecoute...' : 'Ecrivez ou parlez...'"
                    ></textarea>
                </div>

                {{-- Alpine decides: the Web Speech API is a browser feature,
                     not something the server can know about. --}}
                <button
                    type="button"
                    x-show="supported"
                    x-cloak
                    x-on:click="toggleListening()"
                    x-bind:class="listening ? 'bg-red-500 text-white' : 'text-ink-500 hover:bg-ink-100'"
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-xl transition"
                    x-bind:aria-pressed="listening"
                    title="Parler"
                    aria-label="Parler"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V5.25a3 3 0 0 1 6 0v7.5a3 3 0 0 1-3 3Z"/>
                    </svg>
                </button>

                <label
                    class="grid h-10 w-10 shrink-0 cursor-pointer place-items-center rounded-xl text-ink-500 transition hover:bg-ink-100"
                    title="Envoyer une photo"
                    aria-label="Envoyer une photo"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 12.75h.008v.008H18V12.75Zm-3 7.5h.008v.008H15V20.25Zm3-4.5h.008v.008H18v-.008ZM12 15.75h.008v.008H12v-.008Z"/>
                    </svg>
                    <input type="file" wire:model="photo" accept="image/*" capture="environment" class="sr-only">
                </label>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="send"
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-600 text-white transition hover:bg-brand-700 disabled:opacity-50"
                    aria-label="Envoyer"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.126A59.768 59.768 0 0 1 21.485 12 59.77 59.77 0 0 1 3.27 20.876L5.999 12Zm0 0h7.5"/>
                    </svg>
                </button>
            </form>

            <p class="mt-1.5 text-center text-[11px] text-ink-400">
                Les reponses s'appuient uniquement sur le catalogue AutoAlert.
            </p>
        </div>
    </section>
</div>