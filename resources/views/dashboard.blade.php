<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Bonjour, {{ auth()->user()->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <a href="{{ Route::has('trajets.index') ? route('trajets.index') : '#' }}"
                   class="block bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 hover:ring-2 hover:ring-indigo-500 transition">
                    <div class="text-lg font-semibold">Rechercher un trajet</div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Trouvez un départ, choisissez votre siège et réservez.</p>
                </a>

                @if (Route::has('voyageur.reservations.index'))
                    <a href="{{ route('voyageur.reservations.index') }}"
                       class="block bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 hover:ring-2 hover:ring-indigo-500 transition">
                        <div class="text-lg font-semibold">Mes réservations</div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Consultez l'état de vos réservations et vos billets.</p>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>