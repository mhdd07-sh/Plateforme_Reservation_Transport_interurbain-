<div class="overflow-x-auto">
    <table class="min-w-full text-left">
        <thead class="border-b border-outline-variant/40 font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
            <tr>
                <th class="px-4 py-3">Trajet</th>
                <th class="px-4 py-3">Départ</th>
                <th class="px-4 py-3">Véhicule</th>
                <th class="px-4 py-3">Tarif</th>
                <th class="px-4 py-3">Places prises</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant/30 font-body-md text-body-md">
            @forelse ($trajets as $trajet)
                <tr class="hover:bg-surface-container-low/60">
                    <td class="whitespace-nowrap px-4 py-4 font-label-lg text-label-lg">{{ $trajet->ville_depart }} → {{ $trajet->ville_arrivee }}</td>
                    <td class="whitespace-nowrap px-4 py-4">
                        <div>{{ $trajet->date_depart->translatedFormat('l d F') }}</div>
                        <div class="font-body-sm text-body-sm text-on-surface-variant">{{ substr($trajet->heure_depart, 0, 5) }}</div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="font-label-lg text-label-lg">{{ $trajet->vehicule->immatriculation }}</div>
                        <div class="font-body-sm text-body-sm text-on-surface-variant">
                            {{ \App\Models\Vehicule::libelleType($trajet->vehicule->type_vehicule) }} {{ $trajet->vehicule->capacite }} pl.
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-4">{{ number_format($trajet->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                    <td class="px-4 py-4">
                        @include('transporteur._remplissage', ['occupes' => $trajet->sieges_retenus_count, 'capacite' => $trajet->vehicule->capacite])
                    </td>
                    <td class="px-4 py-4"><x-ui.badge :tone="$trajet->toneStatut()">{{ $trajet->libelleStatut() }}</x-ui.badge></td>
                    <td class="px-4 py-4">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('transporteur.trajets.show', $trajet) }}" title="Détails"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container">
                                <x-ui.icon>visibility</x-ui.icon>
                            </a>

                            @can('update', $trajet)
                                <a href="{{ route('transporteur.trajets.edit', $trajet) }}" title="Modifier"
                                   class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-primary-container hover:bg-primary-container/10">
                                    <x-ui.icon>edit</x-ui.icon>
                                </a>
                            @endcan

                            @can('publier', $trajet)
                                <form method="POST" action="{{ route('transporteur.trajets.publier', $trajet) }}">
                                    @csrf
                                    <button type="submit" title="Publier"
                                            class="inline-flex h-9 items-center gap-1 rounded-lg px-2 font-label-md text-label-md text-primary-container hover:bg-primary-container/10">
                                        <x-ui.icon>publish</x-ui.icon> Publier
                                    </button>
                                </form>
                            @endcan

                            @can('annuler', $trajet)
                                <form method="POST" action="{{ route('transporteur.trajets.annuler', $trajet) }}"
                                      onsubmit="return confirm('Annuler ce trajet ?')">
                                    @csrf
                                    <button type="submit" title="Annuler le trajet"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-secondary hover:bg-secondary-container/20">
                                        <x-ui.icon>event_busy</x-ui.icon>
                                    </button>
                                </form>
                            @endcan

                            @can('delete', $trajet)
                                <form method="POST" action="{{ route('transporteur.trajets.destroy', $trajet) }}"
                                      onsubmit="return confirm('Supprimer ce trajet ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Supprimer"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-error hover:bg-error-container/50">
                                        <x-ui.icon>delete</x-ui.icon>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <x-ui.icon class="text-outline">search_off</x-ui.icon>
                        <p class="mt-2 font-headline-sm text-headline-sm">Aucun trajet trouvé</p>
                        <p class="font-body-md text-body-md text-on-surface-variant">Aucun trajet ne correspond au statut sélectionné.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>