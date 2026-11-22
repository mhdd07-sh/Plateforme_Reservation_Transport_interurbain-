@props(['tone' => 'neutral'])

@php
    $couleurs = match ($tone) {
        'success' => 'bg-primary-fixed text-on-primary-fixed-variant',
        'warning' => 'bg-secondary-container text-on-secondary-container',
        'danger' => 'bg-error-container text-on-error-container',
        'info' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
        default => 'bg-surface-container-high text-on-surface-variant',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-label-md text-label-md ' . $couleurs]) }}>
    {{ $slot }}
</span>