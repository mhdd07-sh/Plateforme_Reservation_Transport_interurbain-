<x-app-layout>
    <x-slot name="header">
        <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Rechercher un trajet</h1>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Filtres --}}
            <form method="GET" action="{{ route('trajets.index') }}"
                  class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6 items-end">
                <div>
                    <x-input-label for="depart" value="Départ" />
                    <x-text-input id="depart" name="depart" type="text" list="villes" class="mt-1 block w-full" :value="request('depart')" />
                </div>
                <div>
                    <x-input-label for="arrivee" value="Arrivée" />
                    <x-text-input id="arrivee" name="arrivee" type="text" list="villes" class="mt-1 block w-full" :value="request('arrivee')" />
                </div>
                <div>
                    <x-input-label for="date" value="Date" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="request('date')" />
                </div>
                <div>
                    <x-input-label for="prix_max" value="Prix max (FCFA)" />
                    <x-text-input id="prix_max" name="prix_max" type="number" min="0" step="500" class="mt-1 block w-full" :value="request('prix_max')" />
                </div>
                <div>
                    <x-input-label for="tri" value="Trier par" />
                    <select id="tri" name="tri"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="heure" @selected(request('tri', 'heure') === 'heure')>Heure de départ</option>
                        <option value="prix" @selected(request('tri') === 'prix')>Prix croissant</option>
                    </select>
                </div>
                <x-primary-button class="justify-center">Rechercher</x-primary-button>
            </form>

            <datalist id="villes">
                @foreach (config('transport.villes', []) as $ville)
                    <option value="{{ $ville }}"></option>
                @endforeach
            </datalist>

            @if ($errors->any())
                <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-800 dark:bg-red-900/40 dark:text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Résultats --}}
            <p class="mt-6 mb-3 text-sm text-gray-600 dark:text-gray-400">
                {{ $trajets->total() }} trajet(s) trouvé(s)
            </p>

            <div class="space-y-3">
                @forelse ($trajets as $trajet)
                    @php $libres = max(0, $trajet->vehicule->capacite - $trajet->sieges_retenus_count); @endphp

                    <a href="{{ route('trajets.show', $trajet) }}"
                       class="block bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 hover:ring-2 hover:ring-indigo-500 transition">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <div class="text-lg font-semibold">{{ $trajet->ville_depart }} → {{ $trajet->ville_arrivee }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $trajet->date_depart->translatedFormat('l d F Y') }} à {{ substr($trajet->heure_depart, 0, 5) }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $trajet->transporteur->nom_compagnie ?? 'Transporteur' }} · {{ ucfirst($trajet->vehicule->type_vehicule) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-bold text-indigo-600">{{ number_format($trajet->prix_unitaire, 0, ',', ' ') }} FCFA</div>
                                @if ($libres === 0)
                                    <div class="text-sm font-medium text-red-600">Complet</div>
                                @else
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $libres }} place(s) restante(s)</div>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-8 text-center text-gray-600 dark:text-gray-400">
                        Aucun trajet ne correspond à votre recherche. Essayez une autre date ou une autre ville.
                    </div>
                @endforelse
            </div>

            <div class="mt-6">{{ $trajets->links() }}</div>
        </div>
    </div>
</x-app-layout>