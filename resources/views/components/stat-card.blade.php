@props(['label', 'valeur', 'detail' => null, 'icone' => null, 'tone' => 'success'])

@php
    $couleurs = [
        'success' => 'bg-primary-fixed text-on-primary-fixed-variant',
        'warning' => 'bg-secondary-fixed text-on-secondary-fixed-variant',
        'info' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
        'danger' => 'bg-error-container text-on-error-container',
        'neutral' => 'bg-surface-container-high text-on-surface-variant',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">{{ $label }}</div>
        @if ($icone)
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $couleurs[$tone] ?? $couleurs['success'] }}">
                <x-ui.icon>{{ $icone }}</x-ui.icon>
            </span>
        @endif
    </div>
    <div class="mt-1 font-headline-lg text-headline-lg">{{ $valeur }}</div>
    @if ($detail)
        <div class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ $detail }}</div>
    @endif
</div>