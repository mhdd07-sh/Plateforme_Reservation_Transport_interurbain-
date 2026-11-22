@php
    $vehicule = $trajet->vehicule;
    $libres = max(0, $vehicule->capacite - count($occupes));
    $user = auth()->user();
    $reservationOuverte = Route::has('voyageur.reservations.store');
    $selectionnable = $user?->estVoyageur() && $reservationOuverte && $libres > 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('trajets.index') }}" class="text-sm text-indigo-600 hover:underline">← Retour aux résultats</a>
        <h1 class="mt-1 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $trajet->ville_depart }} → {{ $trajet->ville_arrivee }}
        </h1>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Informations du trajet --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 lg:col-span-1 h-fit">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Départ</div>
                    <div class="font-semibold">{{ $trajet->date_depart->translatedFormat('l d F Y') }}</div>
                    <div class="text-lg font-bold">{{ substr($trajet->heure_depart, 0, 5) }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Transporteur</div>
                    <div class="font-semibold">{{ $trajet->transporteur->nom_compagnie ?? 'Transporteur' }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Véhicule</div>
                    <div class="font-semibold">{{ ucfirst($vehicule->type_vehicule) }} · {{ $vehicule->marque }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $vehicule->capacite }} places</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Prix par place</div>
                    <div class="text-2xl font-bold text-indigo-600">{{ number_format($trajet->prix_unitaire, 0, ',', ' ') }} FCFA</div>
                </div>
                <div>
                    @if ($libres === 0)
                        <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800">Complet</span>
                    @else
                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">{{ $libres }} place(s) restante(s)</span>
                    @endif
                </div>
            </div>

            {{-- Plan des sièges --}}
            <form method="POST"
                  action="{{ $selectionnable ? route('voyageur.reservations.store', $trajet) : '#' }}"
                  x-data="{ selection: [], prix: {{ $trajet->prix_unitaire }}, max: {{ \App\Services\ReservationService::MAX_SIEGES_PAR_RESERVATION }} }"
                  class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 lg:col-span-2">
                @csrf

                <h2 class="text-lg font-semibold">Plan du véhicule</h2>

                <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                    <span class="inline-flex items-center gap-2"><span class="h-4 w-4 rounded border border-green-500 bg-green-50"></span> Libre</span>
                    <span class="inline-flex items-center gap-2"><span class="h-4 w-4 rounded bg-indigo-600"></span> Votre choix</span>
                    <span class="inline-flex items-center gap-2"><span class="h-4 w-4 rounded bg-gray-300 dark:bg-gray-600"></span> Occupé</span>
                </div>

                <div class="mt-6 mx-auto max-w-xs rounded-2xl border-2 border-gray-300 dark:border-gray-600 p-4">
                    <div class="mb-3 text-center text-xs text-gray-500 dark:text-gray-400">Avant du véhicule</div>

                    <div class="space-y-2">
                        @foreach ($vehicule->sieges->chunk(4) as $rangee)
                            <div class="flex justify-center gap-2">
                                @foreach ($rangee as $siege)
                                    @if ($loop->index === 2)
                                        <div class="w-6"></div>
                                    @endif

                                    @if (in_array($siege->id, $occupes))
                                        <span title="Déjà réservé"
                                              class="flex h-11 w-11 cursor-not-allowed items-center justify-center rounded-md bg-gray-300 text-sm text-gray-500 dark:bg-gray-600 dark:text-gray-400">
                                            {{ $siege->numero }}
                                        </span>
                                    @elseif ($selectionnable)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="sieges[]" value="{{ $siege->id }}" x-model="selection" class="peer sr-only"
                                                   :disabled="selection.length >= max && ! selection.includes('{{ $siege->id }}')">
                                            <span class="flex h-11 w-11 items-center justify-center rounded-md border border-green-500 bg-green-50 text-sm font-medium text-green-800 hover:bg-green-100 peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-disabled:cursor-not-allowed peer-disabled:opacity-40">
                                                {{ $siege->numero }}
                                            </span>
                                        </label>
                                    @else
                                        <span class="flex h-11 w-11 items-center justify-center rounded-md border border-green-500 bg-green-50 text-sm font-medium text-green-800">
                                            {{ $siege->numero }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                @error('sieges')
                    <p class="mt-4 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @if ($selectionnable)
                    <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <span x-text="selection.length"></span> place(s) choisie(s)
                            </div>
                            <div class="text-xl font-bold text-indigo-600"
                                 x-text="new Intl.NumberFormat('fr-FR').format(selection.length * prix) + ' FCFA'"></div>
                        </div>
                        <x-primary-button x-bind:disabled="selection.length === 0" class="disabled:cursor-not-allowed disabled:opacity-50">
                            Réserver
                        </x-primary-button>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Vos sièges sont retenus {{ \App\Models\Reservation::DELAI_EXPIRATION_MINUTES }} minutes pour vous laisser le temps de payer.
                        Maximum {{ \App\Services\ReservationService::MAX_SIEGES_PAR_RESERVATION }} sièges par réservation.
                    </p>
                @elseif ($libres === 0)
                    <p class="mt-6 text-sm font-medium text-red-600">Ce trajet est complet.</p>
                @elseif (! $user)
                    <div class="mt-6 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Connectez-vous pour choisir vos sièges et réserver.</p>
                        <div class="mt-3 flex gap-3">
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">Se connecter</a>
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">Créer un compte</a>
                        </div>
                    </div>
                @elseif (! $user->estVoyageur())
                    <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">Seuls les comptes voyageurs peuvent réserver.</p>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>