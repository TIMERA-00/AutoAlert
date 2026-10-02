@props(['type' => 'info'])

@php
    $tones = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'error' => 'border-red-200 bg-red-50 text-red-800',
        'info' => 'border-ink-200 bg-white text-ink-700',
    ];
@endphp

<div class="pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg {{ $tones[$type] ?? $tones['info'] }}">
    <span class="flex-1 text-sm">{{ $slot }}</span>
    <button type="button" x-on:click="dispatch('toast-dismiss')" class="text-current opacity-60 hover:opacity-100" aria-label="Fermer">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
