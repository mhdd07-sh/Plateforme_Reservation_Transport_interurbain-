<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Mes véhicules
            </h2>
            <a href="{{ route('transporteur.vehicules.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                Ajouter un véhicule
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('transporteur._menu')

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                @if ($vehicules->isEmpty())
                    <p class="p-6 text-gray-600 dark:text-gray-300">
                        Aucun véhicule pour le moment.
                        <a href="{{ route('transporteur.vehicules.create') }}" class="text-indigo-600 hover:underline">Ajouter votre premier véhicule</a>.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/40 text-left text-gray-600 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Immatriculation</th>
                                    <th class="px-4 py-3 font-medium">Marque</th>
                                    <th class="px-4 py-3 font-medium">Type</th>
                                    <th class="px-4 py-3 font-medium">Capacité</th>
                                    <th class="px-4 py-3 font-medium">Trajets</th>
                                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-200">
                                @foreach ($vehicules as $vehicule)
                                    <tr>
                                        <td class="px-4 py-3 font-medium">{{ $vehicule->immatriculation }}</td>
                                        <td class="px-4 py-3">{{ $vehicule->marque }}</td>
                                        <td class="px-4 py-3">{{ ucfirst($vehicule->type_vehicule) }}</td>
                                        <td class="px-4 py-3">{{ $vehicule->capacite }} places</td>
                                        <td class="px-4 py-3">{{ $vehicule->trajets_count }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('transporteur.vehicules.edit', $vehicule) }}"
                                               class="text-indigo-600 hover:underline mr-3">Modifier</a>
                                            <form method="POST" action="{{ route('transporteur.vehicules.destroy', $vehicule) }}"
                                                  class="inline" onsubmit="return confirm('Supprimer ce véhicule ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="mt-4">{{ $vehicules->links() }}</div>
        </div>
    </div>
</x-app-layout>