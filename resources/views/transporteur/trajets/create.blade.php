<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-8">
        <a href="{{ route('transporteur.trajets.index') }}" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container hover:underline">
            <x-ui.icon>arrow_back</x-ui.icon> Mes trajets
        </a>

        <x-page-header titre="Créer un trajet" surtitre="Espace transporteur"
            soustitre="Programmez un départ : il sera visible des voyageurs dès sa publication." />

        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card sm:p-8">
            @if ($vehicules->where('statut', 'actif')->isEmpty())
                <p class="font-body-md text-body-md text-on-surface-variant">
                    Vous devez d'abord
                    <a href="{{ route('transporteur.vehicules.create') }}" class="font-label-lg text-label-lg text-primary-container hover:underline">ajouter un véhicule actif</a>
                    avant de créer un trajet.
                </p>
            @else
                <form method="POST" action="{{ route('transporteur.trajets.store') }}">
                    @csrf
                    @include('transporteur.trajets._form')

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <x-secondary-button type="submit" name="action" value="brouillon">Enregistrer en brouillon</x-secondary-button>
                        <x-primary-button name="action" value="publier">Enregistrer et publier</x-primary-button>
                        <a href="{{ route('transporteur.trajets.index') }}" class="px-2 font-label-lg text-label-lg text-on-surface-variant hover:underline">Annuler</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>