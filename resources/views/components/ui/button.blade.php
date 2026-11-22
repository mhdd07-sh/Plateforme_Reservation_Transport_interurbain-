@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg px-5 min-h-[44px] font-label-lg text-label-lg transition active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-primary-container/30 disabled:cursor-not-allowed disabled:opacity-50';

    $variantes = [
        'primary' => 'bg-primary-container text-on-primary hover:bg-primary',
        'accent' => 'bg-secondary-container text-on-secondary-fixed hover:bg-secondary-fixed-dim',
        'outline' => 'border border-primary-container text-primary-container hover:bg-primary-container/5',
        'outline-light' => 'border border-white/60 text-white hover:bg-white/10',
        'light' => 'border border-outline-variant bg-surface-container-lowest text-primary-container hover:bg-surface-container-low',
        'ghost' => 'text-primary-container hover:bg-primary-container/5',
        'danger' => 'bg-error text-on-error hover:opacity-90',
    ];

    $classes = $base . ' ' . ($variantes[$variant] ?? $variantes['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif