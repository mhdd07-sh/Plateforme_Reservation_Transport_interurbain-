@php
    $dateChoisie = request('date');
    $jourPrecedent = $dateChoisie ? \Illuminate\Support\Carbon::parse($dateChoisie)->subDay()->toDateString() : today()->subDay()->toDateString();
    $jourSuivant = $dateChoisie ? \Illuminate\Support\Carbon::parse($dateChoisie)->addDay()->toDateString() : today()->addDay()->toDateString();
    $filtresActifs = request()->filled('q') || request()->filled('transporteur') || request()->filled('statut') || request()->filled('date');
    $selectCss = 'mt-1 block min-h-[48px] w-full rounded-lg border border-[#D1D5DB] bg-white px-4 font-body-md text-body-md focus:border-primary-container focus:ring-2 focus:ring-primary-container/15';
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <x-page-header titre="Supervision des trajets" surtitre="Administration · En direct"
            soustitre="Suivi de l'ensemble des départs interurbains programmés sur la plateforme." />

        <section class="grid grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-5">
            <x-stat-card label="Publiés" :valeur="$stats['publies']" icone="check" detail="En vente active" />
            <x-stat-card label="Brouillons" :valeur="$stats['brouillons']" icone="edit_note" tone="info" detail="Configuration transporteur" />
            <x-stat-card label="Complets" :valeur="$stats['complets']" icone="event_seat" tone="warning" detail="100% de remplissage" />
            <x-stat-card label="Annulés" :valeur="$stats['annules']" icone="block" tone="danger" detail="Départs annulés" />

            <div class="rounded-xl bg-primary p-5 text-on-primary shadow-elevated">
                <div class="inline-flex items-center gap-1 font-label-md text-label-md uppercase tracking-wider text-on-primary-container">
                    <x-ui.icon>speed</x-ui.icon> Indicateur du jour
                </div>
                <div class="mt-2 font-headline-lg text-headline-lg">{{ $stats['passagers_aujourdhui'] }}</div>
                <div class="font-body-sm text-body-sm text-on-primary-container">passager(s) attendu(s) aujourd'hui</div>
            </div>
        </section>

        {{-- Filtres --}}
        <form method="GET" action="{{ route('admin.trajets.index') }}"
              class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
            <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <x-input-label for="q" value="Départ ou arrivée" />
                    <x-text-input id="q" name="q" type="search" class="mt-1" :value="request('q')" placeholder="Dakar, Thiès, Touba..." />
                </div>
                <div>
                    <x-input-label for="transporteur" value="Compagnie" />
                    <select id="transporteur" name="transporteur" class="{{ $selectCss }}">
                        <option value="">Toutes les compagnies ({{ $transporteurs->count() }})</option>
                        @foreach ($transporteurs as $t)
                            <option value="{{ $t->id }}" @selected((string) request('transporteur') === (string) $t->id)>{{ $t->nomAffiche() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="statut" value="Statut" />
                    <select id="statut" name="statut" class="{{ $selectCss }}">
                        <option value="">Départs à venir</option>
                        @foreach (['publie' => 'Publié', 'complet' => 'Complet', 'brouillon' => 'Brouillon', 'annule' => 'Annulé', 'termine' => 'Terminé'] as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="date" value="Date" />
                    <div class="mt-1 flex items-center gap-1">
                        <a href="{{ route('admin.trajets.index', array_filter(array_merge(request()->query(), ['date' => $jourPrecedent]))) }}" title="Jour précédent"
                           class="inline-flex h-12 w-10 items-center justify-center rounded-lg border border-[#D1D5DB] hover:bg-surface-container-low"><x-ui.icon>chevron_left</x-ui.icon></a>
                        <x-text-input id="date" name="date" type="date" :value="$dateChoisie" />
                        <a href="{{ route('admin.trajets.index', array_filter(array_merge(request()->query(), ['date' => $jourSuivant]))) }}" title="Jour suivant"
                           class="inline-flex h-12 w-10 items-center justify-center rounded-lg border border-[#D1D5DB] hover:bg-surface-container-low"><x-ui.icon>chevron_right</x-ui.icon></a>
                    </div>
                </div>
                <x-primary-button>Filtrer <x-ui.icon>filter_alt</x-ui.icon></x-primary-button>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2 font-body-sm text-body-sm">
                <span class="font-label-md text-label-md text-on-surface-variant">Filtres rapides :</span>
                <a href="{{ route('admin.trajets.index') }}" class="rounded-lg bg-surface-container px-3 py-1">Tous</a>
                <a href="{{ route('admin.trajets.index', ['statut' => 'complet']) }}" class="rounded-lg bg-secondary-fixed px-3 py-1 text-on-secondary-fixed">Complets ({{ $stats['complets'] }})</a>
                <a href="{{ route('admin.trajets.index', ['statut' => 'annule']) }}" class="rounded-lg bg-error-container px-3 py-1 text-on-error-container">Annulés ({{ $stats['annules'] }})</a>
                @if ($filtresActifs)
                    <a href="{{ route('admin.trajets.index') }}" class="ml-auto inline-flex items-center gap-1 font-label-md text-label-md text-primary-container hover:underline"><x-ui.icon>restart_alt</x-ui.icon> Réinitialiser les filtres</a>
                @endif
            </div>
        </form>

        {{-- Tableau --}}
        <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-4 shadow-card">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="border-b border-outline-variant/40 font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
                        <tr>
                            <th class="px-4 py-3">Trajet</th>
                            <th class="px-4 py-3">Compagnie</th>
                            <th class="px-4 py-3">Départ</th>
                            <th class="px-4 py-3">Véhicule</th>
                            <th class="px-4 py-3">Tarif</th>
                            <th class="px-4 py-3">Remplissage</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30 font-body-md text-body-md">
                        @forelse ($trajets as $t)
                            @php
                                $capacite = $t->vehicule->capacite;
                                $complet = $t->statut === 'publie' && $t->depart_le->isFuture() && $capacite > 0 && $t->sieges_retenus_count >= $capacite;
                                $jour = $t->depart_le->isToday() ? "Aujourd'hui" : ($t->depart_le->isTomorrow() ? 'Demain' : $t->date_depart->translatedFormat('D d M'));
                            @endphp
                            <tr class="{{ $t->statut === 'annule' ? 'bg-error-container/30' : '' }} hover:bg-surface-container-low/60">
                                <td class="whitespace-nowrap px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-fixed text-on-primary-fixed-variant"><x-ui.icon>alt_route</x-ui.icon></span>
                                        <span class="font-label-lg text-label-lg {{ $t->statut === 'annule' ? 'line-through text-on-surface-variant' : '' }}">{{ $t->ville_depart }} → {{ $t->ville_arrivee }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">{{ $t->transporteur->nomAffiche() }}</td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    <div class="font-label-lg text-label-lg">{{ $jour }} à {{ substr($t->heure_depart, 0, 5) }}</div>
                                    <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $t->depart_le->isPast() ? 'Départ passé' : 'Dans ' . $t->depart_le->diffForHumans(null, true) }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div>{{ \App\Models\Vehicule::libelleType($t->vehicule->type_vehicule) }}</div>
                                    <code class="rounded-md bg-surface-container px-1.5 py-0.5 font-label-sm text-label-sm">{{ $t->vehicule->immatriculation }}</code>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 font-label-lg text-label-lg">{{ number_format($t->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-4">@include('transporteur._remplissage', ['occupes' => $t->sieges_retenus_count, 'capacite' => $capacite])</td>
                                <td class="px-4 py-4">
                                    @if ($complet)
                                        <x-ui.badge tone="danger">Complet</x-ui.badge>
                                    @else
                                        <x-ui.badge :tone="$t->toneStatut()">{{ $t->libelleStatut() }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($t->statut === 'publie' && $t->sieges_retenus_count === 0)
                                            <form method="POST" action="{{ route('admin.trajets.retirer', $t) }}" onsubmit="return confirm('Retirer ce trajet de la vente ? Il repassera en brouillon.')">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-secondary-container px-3 py-1.5 font-label-md text-label-md text-on-secondary-container hover:bg-secondary-fixed-dim">
                                                    <x-ui.icon>remove_shopping_cart</x-ui.icon> Retirer
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.trajets.show', $t) }}" class="inline-flex items-center gap-1 rounded-lg bg-surface-container-low px-3 py-1.5 font-label-md text-label-md text-primary-container hover:bg-surface-container">
                                            <x-ui.icon>visibility</x-ui.icon> Détail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-12 text-center text-on-surface-variant">Aucun trajet ne correspond à ces critères.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <span class="font-body-sm text-body-sm text-on-surface-variant">Affichage de {{ $trajets->count() }} sur {{ $trajets->total() }} trajet(s)</span>
                {{ $trajets->links() }}
            </div>
        </section>
    </div>
</x-app-layout>