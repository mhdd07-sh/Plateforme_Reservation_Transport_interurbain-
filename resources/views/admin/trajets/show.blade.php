@php $v = $trajet->vehicule; @endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <a href="{{ route('admin.trajets.index') }}" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container hover:underline">
            <x-ui.icon>arrow_back</x-ui.icon> Supervision des trajets
        </a>

        <x-page-header :titre="$trajet->ville_depart . ' → ' . $trajet->ville_arrivee"
            :soustitre="$trajet->date_depart->translatedFormat('l d F Y') . ' à ' . substr($trajet->heure_depart, 0, 5) . ' · ' . $trajet->transporteur->nomAffiche()"
            surtitre="Détail du trajet">
            <x-ui.badge :tone="$trajet->toneStatut()">{{ $trajet->libelleStatut() }}</x-ui.badge>
            @if ($trajet->statut === 'publie' && $actives === 0)
                <form method="POST" action="{{ route('admin.trajets.retirer', $trajet) }}" onsubmit="return confirm('Retirer ce trajet de la vente ?')">
                    @csrf
                    <x-ui.button type="submit" variant="accent"><x-ui.icon>remove_shopping_cart</x-ui.icon> Retirer de la vente</x-ui.button>
                </form>
            @endif
        </x-page-header>

        <section class="grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Véhicule" :valeur="$v->immatriculation" icone="directions_bus" tone="neutral"
                :detail="\App\Models\Vehicule::libelleType($v->type_vehicule) . ' · ' . $v->marque" />
            <x-stat-card label="Tarif par place" :valeur="number_format($trajet->prix_unitaire, 0, ',', ' ') . ' FCFA'" icone="payments" />
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                <div class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Remplissage</div>
                <div class="mt-2">@include('transporteur._remplissage', ['occupes' => $occupes, 'capacite' => $v->capacite])</div>
            </div>
            <x-stat-card label="Revenus confirmés" :valeur="number_format($revenus, 0, ',', ' ') . ' FCFA'" icone="account_balance_wallet" tone="info" />
        </section>

        <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
            <h2 class="font-headline-md text-headline-md">Passagers et réservations</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="border-b border-outline-variant/40 font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
                        <tr><th class="px-4 py-3">Passager</th><th class="px-4 py-3">Référence</th><th class="px-4 py-3">Siège(s)</th><th class="px-4 py-3">Montant</th><th class="px-4 py-3">Statut</th></tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30 font-body-md text-body-md">
                        @forelse ($reservations as $r)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-label-lg text-label-lg">{{ $r->user->name }}</div>
                                    <div class="font-body-sm text-body-sm text-on-surface-variant">+221 {{ \App\Support\Telephone::formater($r->user->telephone) }}</div>
                                </td>
                                <td class="px-4 py-3 font-label-lg text-label-lg text-primary-container">{{ $r->reference }}</td>
                                <td class="px-4 py-3">{{ $r->sieges->pluck('numero')->sort()->implode(', ') ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ number_format($r->montant_total, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-3"><x-ui.badge :tone="$r->toneStatut()">{{ $r->libelleStatut() }}</x-ui.badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-on-surface-variant">Aucune réservation sur ce trajet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>