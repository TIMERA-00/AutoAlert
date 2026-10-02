@props(['title', 'description' => null, 'icon' => 'search'])

<div class="card flex flex-col items-center gap-3 p-12 text-center">
    @if ($icon === 'search')
        <svg class="h-12 w-12 text-ink-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
    @else
        <svg class="h-12 w-12 text-ink-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
        </svg>
    @endif

    <h3 class="text-base font-semibold text-ink-900">{{ $title }}</h3>
    @if ($description)
        <p class="max-w-md text-sm text-ink-500">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
