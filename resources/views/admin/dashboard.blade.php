<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Administration
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'Voyageurs' => $stats['voyageurs'],
                    'Transporteurs validés' => $stats['transporteurs'],
                    'Transporteurs en attente' => $stats['transporteurs_en_attente'],
                    'Trajets publiés' => $stats['trajets_publies'],
                    'Réservations confirmées' => $stats['reservations_confirmees'],
                ] as $libelle => $valeur)
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $libelle }}</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $valeur }}</div>
                    </div>
                @endforeach
            </div>

            <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                La gestion des utilisateurs et les statistiques détaillées arrivent en phase 4.
            </p>
        </div>
    </div>
</x-app-layout>