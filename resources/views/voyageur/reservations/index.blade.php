@php
    $badges = [
        'en_attente' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200',
        'confirmee' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200',
        'annulee' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
        'expiree' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200',
    ];
    $libelles = ['en_attente' => 'En attente de paiement', 'confirmee' => 'Confirmée', 'annulee' => 'Annulée', 'expiree' => 'Expirée'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Mes réservations</h1>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="space-y-3">
                @forelse ($reservations as $reservation)
                    <a href="{{ route('voyageur.reservations.show', $reservation) }}"
                       class="block bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 hover:ring-2 hover:ring-indigo-500 transition">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ $reservation->trajet->ville_depart }} → {{ $reservation->trajet->ville_arrivee }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $reservation->trajet->date_depart->translatedFormat('l d F Y') }} à {{ substr($reservation->trajet->heure_depart, 0, 5) }}
                                    · {{ $reservation->nombre_places }} place(s)
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Réf. {{ $reservation->reference }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-indigo-600">{{ number_format($reservation->montant_total, 0, ',', ' ') }} FCFA</div>
                                <span class="mt-1 inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $badges[$reservation->statut] ?? '' }}">
                                    {{ $libelles[$reservation->statut] ?? $reservation->statut }}
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-8 text-center text-gray-600 dark:text-gray-400">
                        Vous n'avez aucune réservation.
                        <a href="{{ route('trajets.index') }}" class="text-indigo-600 hover:underline">Rechercher un trajet</a>.
                    </div>
                @endforelse
            </div>

            <div class="mt-6">{{ $reservations->links() }}</div>
        </div>
    </div>
</x-app-layout>