@props(['actuelle' => 1])

@php $etapes = ['Recherche', 'Détails & sièges', 'Paiement', 'Billet QR']; @endphp

<ol class="flex flex-wrap items-center gap-x-8 gap-y-3 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-4 shadow-card print:hidden">
    @foreach ($etapes as $i => $nom)
        @php
            $n = $i + 1;
            $fait = $n < $actuelle;
            $courante = $n === (int) $actuelle;
        @endphp
        <li class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-full font-label-lg text-label-lg {{ $fait ? 'bg-primary-fixed text-on-primary-fixed-variant' : ($courante ? 'bg-primary-container text-on-primary' : 'bg-surface-container text-on-surface-variant') }}">
                @if ($fait)
                    <x-ui.icon>check</x-ui.icon>
                @else
                    {{ $n }}
                @endif
            </span>
            <span class="leading-tight">
                <span class="block font-label-sm text-label-sm uppercase text-on-surface-variant">Étape {{ $n }}</span>
                <span class="block font-label-lg text-label-lg {{ $courante ? 'text-on-surface' : 'text-on-surface-variant' }}">{{ $nom }}</span>
            </span>
        </li>
    @endforeach
</ol>