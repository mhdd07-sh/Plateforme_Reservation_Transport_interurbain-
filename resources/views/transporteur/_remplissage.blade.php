@php
    $pct = $capacite > 0 ? (int) round($occupes / $capacite * 100) : 0;
    $complet = $capacite > 0 && $occupes >= $capacite;
@endphp

<div class="min-w-[130px]">
    <div class="flex items-center justify-between gap-2 font-label-lg text-label-lg">
        <span>{{ $occupes }} / {{ $capacite }}</span>
        @if ($complet)
            <x-ui.badge tone="danger">Complet</x-ui.badge>
        @else
            <span class="font-label-md text-label-md text-on-surface-variant">{{ $pct }}%</span>
        @endif
    </div>
    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-container-high">
        <div class="h-full rounded-full {{ $pct >= 90 ? 'bg-secondary-container' : 'bg-primary-container' }}" style="width: {{ $pct }}%"></div>
    </div>
</div>