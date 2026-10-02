@props(['label', 'value', 'hint' => null, 'tone' => 'default'])

@php
    $tones = [
        'default' => 'text-ink-900',
        'success' => 'text-emerald-600',
        'warning' => 'text-amber-600',
        'danger' => 'text-red-600',
        'brand' => 'text-brand-600',
    ];
@endphp

<div class="card p-5">
    <p class="text-sm font-medium text-ink-500">{{ $label }}</p>
    <p class="mt-2 text-3xl font-bold tracking-tight {{ $tones[$tone] ?? $tones['default'] }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-ink-400">{{ $hint }}</p>
    @endif
</div>
