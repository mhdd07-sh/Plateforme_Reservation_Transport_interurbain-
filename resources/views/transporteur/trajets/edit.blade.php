<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-8">
        <a href="{{ route('transporteur.trajets.index') }}" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container hover:underline">
            <x-ui.icon>arrow_back</x-ui.icon> Mes trajets
        </a>

        <x-page-header :titre="'Modifier : ' . $trajet->ville_depart . ' → ' . $trajet->ville_arrivee" surtitre="Espace transporteur" />

        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card sm:p-8">
            <form method="POST" action="{{ route('transporteur.trajets.update', $trajet) }}">
                @csrf
                @method('PUT')
                @include('transporteur.trajets._form')

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    @if ($trajet->statut === \App\Models\Trajet::STATUT_BROUILLON)
                        <x-secondary-button type="submit" name="action" value="brouillon">Enregistrer</x-secondary-button>
                        <x-primary-button name="action" value="publier">Enregistrer et publier</x-primary-button>
                    @else
                        <x-primary-button>Mettre à jour</x-primary-button>
                    @endif
                    <a href="{{ route('transporteur.trajets.index') }}" class="px-2 font-label-lg text-label-lg text-on-surface-variant hover:underline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>