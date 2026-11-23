@props(['nom', 'tone' => 'success'])

@php
    $mots = preg_split('/\s+/', trim((string) $nom));
    $initiales = mb_strtoupper(mb_substr($mots[0] ?? '', 0, 1) . mb_substr($mots[1] ?? '', 0, 1));
    $couleurs = [
        'success' => 'bg-primary-fixed text-on-primary-fixed-variant',
        'warning' => 'bg-secondary-fixed text-on-secondary-fixed-variant',
        'info' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
        'danger' => 'bg-error-container text-on-error-container',
        'neutral' => 'bg-surface-container-high text-on-surface-variant',
        'dark' => 'bg-primary-container text-on-primary',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg font-label-lg text-label-lg ' . ($couleurs[$tone] ?? $couleurs['success'])]) }}>{{ $initiales }}</span>