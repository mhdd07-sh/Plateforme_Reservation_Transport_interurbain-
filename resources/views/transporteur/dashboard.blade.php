<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Espace transporteur
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('transporteur._menu')

            @if (! $transporteur)
                <div class="rounded-md bg-yellow-50 p-4 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200">
                    Votre profil transporteur n'est pas encore créé. Contactez l'administrateur.
                </div>
            @elseif ($transporteur->statut === \App\Models\Transporteur::STATUT_EN_ATTENTE)
                <div class="rounded-md bg-yellow-50 p-4 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200">
                    Votre compte est en attente de validation par l'administrateur. Vous pourrez gérer vos véhicules et vos trajets dès sa validation.
                </div>
            @elseif (! $transporteur->estValide())
                <div class="rounded-md bg-red-50 p-4 text-red-800 dark:bg-red-900/40 dark:text-red-200">
                    Votre compte est suspendu. Contactez l'administrateur.
                </div>
            @else
                <p class="mb-4 text-gray-700 dark:text-gray-300">
                    Bienvenue{{ $transporteur->nom_compagnie ? ', ' . $transporteur->nom_compagnie : '' }}.
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Véhicules</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['vehicules'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Trajets publiés</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['trajets_publies'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Réservations confirmées</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['reservations_confirmees'] }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>