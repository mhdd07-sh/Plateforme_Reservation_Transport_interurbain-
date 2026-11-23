<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-8">
        <a href="{{ route('transporteur.vehicules.index') }}" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container hover:underline">
            <x-ui.icon>arrow_back</x-ui.icon> Mes véhicules
        </a>

        <x-page-header titre="Ajouter un véhicule" surtitre="Flotte" />

        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card sm:p-8">
            <form method="POST" action="{{ route('transporteur.vehicules.store') }}">
                @csrf
                @include('transporteur.vehicules._form')

                <div class="mt-8 flex items-center gap-3">
                    <x-primary-button>Enregistrer</x-primary-button>
                    <a href="{{ route('transporteur.vehicules.index') }}" class="px-2 font-label-lg text-label-lg text-on-surface-variant hover:underline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>