@php
    $onglets = [
        'tous' => 'Tous',
        'publie' => 'Publiés',
        'brouillon' => 'Brouillons',
        'termine' => 'Terminés',
        'annule' => 'Annulés',
    ];
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <x-page-header titre="Mes trajets" surtitre="Espace transporteur"
            soustitre="Programmez, publiez et suivez le remplissage de vos trajets interurbains.">
            <x-ui.button :href="route('transporteur.trajets.create')"><x-ui.icon>add_circle</x-ui.icon> Créer un trajet</x-ui.button>
        </x-page-header>

        <div class="flex flex-wrap gap-2">
            @foreach ($onglets as $cle => $libelle)
                <a href="{{ route('transporteur.trajets.index', ['statut' => $cle]) }}"
                   class="rounded-lg px-4 py-2 font-label-lg text-label-lg transition {{ $filtre === $cle ? 'bg-primary-container text-on-primary' : 'border border-outline-variant/40 bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-low' }}">
                    {{ $libelle }} <span class="opacity-70">({{ $compteurs[$cle] }})</span>
                </a>
            @endforeach
        </div>

        <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-4 shadow-card">
            <div class="flex items-center justify-between px-4 pb-2 pt-2">
                <h2 class="font-headline-sm text-headline-sm">Liste des trajets</h2>
                <span class="font-body-sm text-body-sm text-on-surface-variant">{{ $trajets->total() }} trajet(s) affiché(s)</span>
            </div>
            @include('transporteur.trajets._table')
        </section>

        <div>{{ $trajets->links() }}</div>
    </div>
</x-app-layout>