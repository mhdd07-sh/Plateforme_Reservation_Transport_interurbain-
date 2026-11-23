@php
    $onglets = [
        'toutes' => 'Toutes',
        'confirmee' => 'Confirmées',
        'terminee' => 'Terminées',
        'en_attente' => 'En attente',
        'annulee' => 'Annulées',
    ];
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">Mes réservations</h1>
                <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Gérez vos e-billets, vos paiements et vos détails de voyage.</p>
            </div>
            <x-ui.button :href="route('trajets.index')"><x-ui.icon>add_circle</x-ui.icon> Nouveau voyage</x-ui.button>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach ($onglets as $cle => $libelle)
                <a href="{{ route('voyageur.reservations.index', ['statut' => $cle]) }}"
                   class="rounded-lg px-4 py-2 font-label-lg text-label-lg transition {{ $filtre === $cle ? 'bg-primary-container text-on-primary' : 'bg-surface-container-lowest text-on-surface-variant border border-outline-variant/40 hover:bg-surface-container-low' }}">
                    {{ $libelle }} <span class="opacity-70">({{ $compteurs[$cle] }})</span>
                </a>
            @endforeach
        </div>

        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-4 shadow-card">
            @include('voyageur.reservations._table')
        </div>
    </div>
</x-app-layout>