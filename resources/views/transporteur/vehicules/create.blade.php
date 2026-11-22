<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Ajouter un véhicule
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('transporteur._menu')

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('transporteur.vehicules.store') }}">
                    @csrf
                    @include('transporteur.vehicules._form')

                    <div class="flex items-center gap-4 mt-6">
                        <x-primary-button>Enregistrer</x-primary-button>
                        <a href="{{ route('transporteur.vehicules.index') }}"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>