@php
    $badges = [
        'brouillon' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
        'publie' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200',
        'annule' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200',
        'termine' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',
    ];
    $libelles = ['brouillon' => 'Brouillon', 'publie' => 'Publié', 'annule' => 'Annulé', 'termine' => 'Terminé'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Mes trajets
            </h2>
            <a href="{{ route('transporteur.trajets.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                Créer un trajet
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('transporteur._menu')

            <form method="GET" class="mb-4 flex items-center gap-2 text-sm">
                <label for="statut" class="text-gray-700 dark:text-gray-300">Statut :</label>
                <select id="statut" name="statut" onchange="this.form.submit()"
                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">Tous</option>
                    @foreach ($libelles as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </form>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                @if ($trajets->isEmpty())
                    <p class="p-6 text-gray-600 dark:text-gray-300">
                        Aucun trajet trouvé.
                        <a href="{{ route('transporteur.trajets.create') }}" class="text-indigo-600 hover:underline">Créer un trajet</a>.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/40 text-left text-gray-600 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Trajet</th>
                                    <th class="px-4 py-3 font-medium">Départ</th>
                                    <th class="px-4 py-3 font-medium">Véhicule</th>
                                    <th class="px-4 py-3 font-medium">Prix</th>
                                    <th class="px-4 py-3 font-medium">Places prises</th>
                                    <th class="px-4 py-3 font-medium">Statut</th>
                                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-200">
                                @foreach ($trajets as $trajet)
                                    <tr>
                                        <td class="px-4 py-3 font-medium whitespace-nowrap">
                                            {{ $trajet->ville_depart }} → {{ $trajet->ville_arrivee }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $trajet->date_depart->format('d/m/Y') }} à {{ substr($trajet->heure_depart, 0, 5) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $trajet->vehicule->immatriculation }} ({{ ucfirst($trajet->vehicule->type_vehicule) }})
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ number_format($trajet->prix_unitaire, 0, ',', ' ') }} FCFA
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $trajet->sieges_retenus_count }} / {{ $trajet->vehicule->capacite }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $badges[$trajet->statut] ?? '' }}">
                                                {{ $libelles[$trajet->statut] ?? $trajet->statut }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            @can('update', $trajet)
                                                <a href="{{ route('transporteur.trajets.edit', $trajet) }}"
                                                   class="text-indigo-600 hover:underline mr-3">Modifier</a>
                                            @endcan

                                            @can('publier', $trajet)
                                                <form method="POST" action="{{ route('transporteur.trajets.publier', $trajet) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:underline mr-3">Publier</button>
                                                </form>
                                            @endcan

                                            @can('annuler', $trajet)
                                                <form method="POST" action="{{ route('transporteur.trajets.annuler', $trajet) }}" class="inline"
                                                      onsubmit="return confirm('Annuler ce trajet ?')">
                                                    @csrf
                                                    <button type="submit" class="text-orange-600 hover:underline mr-3">Annuler</button>
                                                </form>
                                            @endcan

                                            @can('delete', $trajet)
                                                <form method="POST" action="{{ route('transporteur.trajets.destroy', $trajet) }}" class="inline"
                                                      onsubmit="return confirm('Supprimer ce trajet ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="mt-4">{{ $trajets->links() }}</div>
        </div>
    </div>
</x-app-layout>