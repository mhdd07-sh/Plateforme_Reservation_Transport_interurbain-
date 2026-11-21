<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Créer un trajet
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('transporteur._menu')

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                @if ($vehicules->isEmpty())
                    <p class="text-gray-700 dark:text-gray-300">
                        Vous devez d'abord
                        <a href="{{ route('transporteur.vehicules.create') }}" class="text-indigo-600 hover:underline">ajouter un véhicule</a>
                        avant de créer un trajet.
                    </p>
                @else
                    <form method="POST" action="{{ route('transporteur.trajets.store') }}">
                        @csrf
                        @include('transporteur.trajets._form')

                        <div class="flex flex-wrap items-center gap-3 mt-6">
                            <x-secondary-button type="submit" name="action" value="brouillon">Enregistrer en brouillon</x-secondary-button>
                            <x-primary-button name="action" value="publier">Enregistrer et publier</x-primary-button>
                            <a href="{{ route('transporteur.trajets.index') }}"
                               class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Annuler</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>