@props(['vehicle'])

<article class="card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
    <a href="{{ route('vehicles.show', $vehicle) }}" class="relative block aspect-[4/3] overflow-hidden bg-ink-100">
        @if ($vehicle->mainImage)
            <img src="{{ $vehicle->mainImage->url }}"
                 alt="{{ $vehicle->mainImage->alt ?? $vehicle->title() }}"
                 loading="lazy"
                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <span class="grid h-full w-full place-items-center text-ink-300">
                <svg class="h-16 w-16" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 12.75l1.5-4.5A2.25 2.25 0 0 1 5.9 6.75h12.2a2.25 2.25 0 0 1 2.15 1.5l1.5 4.5M2.25 12.75h19.5m-19.5 0v3.75A2.25 2.25 0 0 0 5.25 18.75h13.5a2.25 2.25 0 0 0 2.25-2.25v-3.75M7.5 16.5h.008v.008H7.5v-.008zm9 0h.008v.008h-.008v-.008z"/>
                </svg>
            </span>
        @endif

        @if ($vehicle->status->value !== 'PUBLISHED')
            <span class="badge absolute left-3 top-3 {{ $vehicle->status->badgeClass() }}">{{ $vehicle->status->label() }}</span>
        @endif

        <span class="absolute right-3 top-3 rounded-lg bg-white/90 px-2 py-1 text-xs font-semibold text-ink-700 backdrop-blur">
            {{ $vehicle->published_at ? $vehicle->published_at->diffForHumans() : 'Bientot' }}
        </span>
    </a>

    <div class="flex flex-1 flex-col gap-3 p-4">
        <div>
            <h3 class="font-semibold text-ink-900">
                <a href="{{ route('vehicles.show', $vehicle) }}" class="hover:text-brand-700">{{ $vehicle->title() }}</a>
            </h3>
            @if ($vehicle->location)
                <p class="mt-0.5 text-xs text-ink-500">{{ $vehicle->location }}</p>
            @endif
        </div>

        <p class="text-lg font-bold text-ink-900">{{ number_format($vehicle->price, 0, ',', ' ') }} FCFA</p>

        <dl class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-ink-600">
            <div class="flex items-center gap-1.5">
                <span class="text-ink-400">Annee</span> {{ $vehicle->year }}
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-ink-400">Km</span> {{ number_format($vehicle->mileage, 0, ',', ' ') }}
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-ink-400">Boite</span> {{ $vehicle->transmission->label() }}
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-ink-400">Carburant</span> {{ $vehicle->fuel->label() }}
            </div>
        </dl>

        <div class="mt-auto flex items-center gap-2 pt-2">
            <a href="{{ route('vehicles.show', $vehicle) }}" class="btn-primary btn-sm flex-1">Voir le vehicule</a>
            <a href="{{ route('source.redirect', $vehicle) }}"
               class="btn-secondary btn-sm"
               title="Ouvrir l annonce originale"
               onclick="event.stopPropagation()">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-7.5 3L21 3m0 0h-5.25M21 3v5.25"/>
                </svg>
            </a>
        </div>
    </div>
</article>