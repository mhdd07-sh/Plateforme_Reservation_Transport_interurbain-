<div class="overflow-x-auto">
    <table class="min-w-full text-left">
        <thead class="border-b border-outline-variant/40 font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
            <tr>
                <th class="px-4 py-3">Réf. et date</th>
                <th class="px-4 py-3">Itinéraire</th>
                <th class="px-4 py-3">Transporteur</th>
                <th class="px-4 py-3">Siège</th>
                <th class="px-4 py-3">Montant</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant/30 font-body-md text-body-md">
            @forelse ($reservations as $r)
                @php $t = $r->trajet; @endphp
                <tr class="hover:bg-surface-container-low/60">
                    <td class="px-4 py-4">
                        <div class="font-label-lg text-label-lg text-primary-container">{{ $r->reference }}</div>
                        <div class="font-body-sm text-body-sm text-on-surface-variant">
                            {{ $t->date_depart->format('d/m/Y') }} · {{ substr($t->heure_depart, 0, 5) }}
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-4 font-label-lg text-label-lg">{{ $t->ville_depart }} → {{ $t->ville_arrivee }}</td>
                    <td class="px-4 py-4">
                        <div>{{ $t->transporteur->nom_compagnie ?? 'Transporteur' }}</div>
                        <div class="font-body-sm text-body-sm text-on-surface-variant">{{ ucfirst($t->vehicule->type_vehicule) }}</div>
                    </td>
                    <td class="px-4 py-4">
                        <span class="rounded-md bg-surface-container px-2 py-1 font-label-md text-label-md">
                            {{ $r->sieges->pluck('numero')->sort()->implode(', ') ?: '—' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-4">
                        <div class="font-label-lg text-label-lg">{{ number_format($r->montant_total, 0, ',', ' ') }} FCFA</div>
                        @if ($r->operateur_paiement)
                            <div class="font-body-sm text-body-sm text-on-surface-variant">{{ \App\Models\Paiement::libelleMoyen($r->operateur_paiement) }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-4"><x-ui.badge :tone="$r->toneStatut()">{{ $r->libelleStatut() }}</x-ui.badge></td>
                    <td class="whitespace-nowrap px-4 py-4 text-right">
                        @if ($r->statutAffiche() === 'en_attente' && Route::has('voyageur.paiement.create'))
                            <a href="{{ route('voyageur.paiement.create', $r) }}" class="font-label-lg text-label-lg text-primary-container hover:underline">Payer</a>
                        @else
                            <a href="{{ route('voyageur.reservations.show', $r) }}" title="Voir" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-primary-container hover:bg-primary-container/10">
                                <x-ui.icon>{{ $r->statut === 'confirmee' ? 'qr_code_2' : 'visibility' }}</x-ui.icon>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-on-surface-variant">Aucune réservation pour le moment.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>