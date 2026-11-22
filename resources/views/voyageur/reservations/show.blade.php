@php
    $trajet = $reservation->trajet;
    $badges = [
        'en_attente' => 'bg-yellow-100 text-yellow-800',
        'confirmee' => 'bg-green-100 text-green-800',
        'annulee' => 'bg-gray-100 text-gray-700',
        'expiree' => 'bg-red-100 text-red-800',
    ];
    $libelles = ['en_attente' => 'En attente de paiement', 'confirmee' => 'Confirmée', 'annulee' => 'Annulée', 'expiree' => 'Expirée'];
    $numeros = $reservation->sieges->pluck('numero')->sort()->implode(', ');
@endphp

<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('voyageur.reservations.index') }}" class="text-sm text-indigo-600 hover:underline">← Mes réservations</a>
        <h1 class="mt-1 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Réservation {{ $reservation->reference }}
        </h1>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 gap-6 lg:grid-cols-3">

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <div class="text-xl font-semibold">{{ $trajet->ville_depart }} → {{ $trajet->ville_arrivee }}</div>
                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium {{ $badges[$reservation->statut] ?? '' }}">
                        {{ $libelles[$reservation->statut] ?? $reservation->statut }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Départ</dt>
                        <dd class="font-semibold">{{ $trajet->date_depart->translatedFormat('l d F Y') }} à {{ substr($trajet->heure_depart, 0, 5) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Transporteur</dt>
                        <dd class="font-semibold">{{ $trajet->transporteur->nom_compagnie ?? 'Transporteur' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Véhicule</dt>
                        <dd class="font-semibold">{{ ucfirst($trajet->vehicule->type_vehicule) }} · {{ $trajet->vehicule->immatriculation }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Sièges</dt>
                        <dd class="font-semibold">{{ $numeros !== '' ? $numeros : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nombre de places</dt>
                        <dd class="font-semibold">{{ $reservation->nombre_places }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Montant total</dt>
                        <dd class="text-xl font-bold text-indigo-600">{{ number_format($reservation->montant_total, 0, ',', ' ') }} FCFA</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 h-fit">
                @if ($reservation->statut === 'en_attente')
                    <div x-data="{
                            fin: {{ $reservation->expire_at->timestamp }},
                            reste: 0,
                            maj() { this.reste = Math.max(0, this.fin - Math.floor(Date.now() / 1000)); },
                            init() { this.maj(); setInterval(() => this.maj(), 1000); }
                         }">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Vos sièges sont retenus encore</div>
                        <div class="text-3xl font-bold" :class="reste < 60 ? 'text-red-600' : ''"
                             x-text="Math.floor(reste / 60) + ':' + String(reste % 60).padStart(2, '0')"></div>
                        <p x-show="reste === 0" class="mt-2 text-sm text-red-600">
                            Le délai est dépassé : rechargez la page, les sièges seront libérés.
                        </p>
                    </div>

                    @if (Route::has('voyageur.paiement.create'))
                        <a href="{{ route('voyageur.paiement.create', $reservation) }}"
                           class="block text-center px-4 py-2 bg-indigo-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                            Payer {{ number_format($reservation->montant_total, 0, ',', ' ') }} FCFA
                        </a>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400">Le paiement en ligne sera disponible prochainement.</p>
                    @endif

                    <form method="POST" action="{{ route('voyageur.reservations.abandonner', $reservation) }}"
                          onsubmit="return confirm('Abandonner cette réservation et libérer les sièges ?')">
                        @csrf
                        <button type="submit" class="w-full text-center text-sm text-red-600 hover:underline">Abandonner la réservation</button>
                    </form>
                @elseif ($reservation->statut === 'confirmee')
                    <p class="text-sm text-green-700 dark:text-green-300">Votre réservation est confirmée. Le billet sera disponible prochainement.</p>
                @elseif ($reservation->statut === 'expiree')
                    <p class="text-sm text-gray-600 dark:text-gray-400">Le délai de paiement est dépassé : les sièges ont été libérés.</p>
                    <a href="{{ route('trajets.show', $trajet) }}" class="text-sm text-indigo-600 hover:underline">Réserver à nouveau</a>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-400">Cette réservation est annulée.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>