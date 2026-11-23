@php
    $onglets = [
        '' => ['Tous', $total],
        'en_attente' => ['En attente', $compteurs['en_attente'] ?? 0],
        'valide' => ['Validés', $compteurs['valide'] ?? 0],
        'suspendu' => ['Suspendus', $compteurs['suspendu'] ?? 0],
        'refuse' => ['Refusés', $compteurs['refuse'] ?? 0],
    ];
    $statutActif = (string) request('statut', '');

    // Anneau de répartition de la flotte
    $couleursFlotte = ['car' => '#0d5c3a', 'tata' => '#fdc425', 'minibus' => '#314b9b'];
    $totalFlotte = (int) $flotte->sum();
    $cumul = 0;
    $segments = [];
    foreach (['car', 'tata', 'minibus'] as $type) {
        $part = $totalFlotte > 0 ? ($flotte[$type] ?? 0) / $totalFlotte * 100 : 0;
        $segments[] = $couleursFlotte[$type] . ' ' . round($cumul, 2) . '% ' . round($cumul + $part, 2) . '%';
        $cumul += $part;
    }
    $anneau = $totalFlotte > 0 ? 'conic-gradient(' . implode(', ', $segments) . ')' : 'conic-gradient(#d9e3f6 0% 100%)';

    $bouton = 'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 font-label-md text-label-md transition';
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <x-page-header titre="Transporteurs" surtitre="Gestion des opérateurs"
            soustitre="Validez les demandes d'adhésion et régulez la flotte des compagnies partenaires.">
            <x-ui.button :href="route('admin.transporteurs.create')"><x-ui.icon>add_circle</x-ui.icon> Ajouter un transporteur</x-ui.button>
        </x-page-header>

        <section class="grid grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Compagnies actives" :valeur="$stats['actives']" icone="verified" />
            <x-stat-card label="Dossiers en attente" :valeur="$stats['en_attente']" icone="hourglass_top" tone="warning" detail="Validation requise" />
            <x-stat-card label="Parc roulant déployé" :valeur="$stats['parc']" icone="directions_bus" tone="neutral" detail="Minibus, autocars et Tata" />
            <x-stat-card label="Lignes ouvertes" :valeur="$stats['lignes']" icone="alt_route" tone="info" detail="Trajets publiés à venir" />
        </section>

        {{-- Filtres --}}
        <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-4 shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    @foreach ($onglets as $cle => [$libelle, $nombre])
                        <a href="{{ route('admin.transporteurs.index', array_filter(['statut' => $cle, 'q' => request('q'), 'tri' => request('tri')])) }}"
                           class="rounded-lg px-3 py-2 font-label-lg text-label-lg {{ $statutActif === (string) $cle ? 'bg-primary-container text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                            {{ $libelle }}
                            <span class="ml-1 rounded-full px-1.5 py-0.5 font-label-sm text-label-sm {{ $cle === 'en_attente' && $nombre > 0 ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container-high text-on-surface-variant' }}">{{ $nombre }}</span>
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('admin.transporteurs.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="statut" value="{{ request('statut') }}">
                    <x-text-input name="q" type="search" class="!min-h-[40px] w-64" :value="request('q')" placeholder="Compagnie, contact ou n° d'agrément" />
                    <select name="tri" onchange="this.form.submit()"
                        class="min-h-[40px] rounded-lg border border-[#D1D5DB] bg-white px-3 font-body-md text-body-md focus:border-primary-container focus:ring-2 focus:ring-primary-container/15">
                        <option value="recents" @selected(request('tri', 'recents') === 'recents')>Plus récents d'abord</option>
                        <option value="anciens" @selected(request('tri') === 'anciens')>Plus anciens d'abord</option>
                        <option value="nom" @selected(request('tri') === 'nom')>Nom (A-Z)</option>
                    </select>
                    <x-ui.button type="submit" variant="light"><x-ui.icon>search</x-ui.icon></x-ui.button>
                </form>
            </div>
        </section>

        {{-- Tableau --}}
        <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-4 shadow-card">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="border-b border-outline-variant/40 font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
                        <tr>
                            <th class="px-4 py-3">Compagnie et contact</th>
                            <th class="px-4 py-3">N° d'agrément</th>
                            <th class="px-4 py-3 text-center">Véhicules</th>
                            <th class="px-4 py-3 text-center">Trajets publiés</th>
                            <th class="px-4 py-3">Inscription</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30 font-body-md text-body-md">
                        @forelse ($transporteurs as $t)
                            <tr class="{{ $t->statut === 'en_attente' ? 'bg-secondary-fixed/20' : '' }} hover:bg-surface-container-low/60">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :nom="$t->nomAffiche()" :tone="$t->statut === 'en_attente' ? 'warning' : ($t->statut === 'valide' ? 'success' : 'danger')" />
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('admin.transporteurs.show', $t) }}" class="font-label-lg text-label-lg hover:text-primary-container hover:underline">{{ $t->nomAffiche() }}</a>
                                                @if ($t->statut === 'en_attente')
                                                    <x-ui.badge tone="warning">Nouvelle demande</x-ui.badge>
                                                @endif
                                            </div>
                                            <div class="font-body-sm text-body-sm text-on-surface-variant">
                                                {{ $t->user->name }} · +221 {{ \App\Support\Telephone::formater($t->user->telephone) }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    @if ($t->numero_agrement)
                                        <code class="rounded-md bg-surface-container px-2 py-1 font-label-md text-label-md">{{ $t->numero_agrement }}</code>
                                    @else
                                        <span class="text-on-surface-variant">Particulier</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center font-label-lg text-label-lg">{{ $t->vehicules_count }}</td>
                                <td class="px-4 py-4 text-center"><x-ui.badge tone="info">{{ $t->trajets_publies_count }}</x-ui.badge></td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $t->created_at->translatedFormat('D d M Y') }}</td>
                                <td class="px-4 py-4"><x-ui.badge :tone="$t->toneStatut()">{{ $t->libelleStatut() }}</x-ui.badge></td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($t->statut === 'en_attente')
                                            <form method="POST" action="{{ route('admin.transporteurs.valider', $t) }}">
                                                @csrf
                                                <button type="submit" class="{{ $bouton }} bg-primary-container text-on-primary hover:bg-primary"><x-ui.icon>check_circle</x-ui.icon> Valider</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.transporteurs.refuser', $t) }}" onsubmit="return confirm('Refuser cette demande ?')">
                                                @csrf
                                                <button type="submit" class="{{ $bouton }} text-on-surface-variant hover:bg-surface-container"><x-ui.icon>cancel</x-ui.icon> Refuser</button>
                                            </form>
                                        @elseif ($t->statut === 'valide')
                                            <form method="POST" action="{{ route('admin.transporteurs.suspendre', $t) }}" onsubmit="return confirm('Suspendre ce transporteur ? Ses trajets ne seront plus visibles des voyageurs. Les réservations existantes restent valides.')">
                                                @csrf
                                                <button type="submit" class="{{ $bouton }} bg-error-container text-on-error-container hover:opacity-80"><x-ui.icon>block</x-ui.icon> Suspendre</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.transporteurs.reactiver', $t) }}">
                                                @csrf
                                                <button type="submit" class="{{ $bouton }} bg-surface-container text-primary-container hover:bg-surface-container-high"><x-ui.icon>play_arrow</x-ui.icon> Réactiver</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.transporteurs.show', $t) }}" class="{{ $bouton }} bg-surface-container-low text-primary-container hover:bg-surface-container">Voir <x-ui.icon>arrow_forward</x-ui.icon></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-12 text-center text-on-surface-variant">Aucun transporteur ne correspond à ces critères.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $transporteurs->links() }}</div>
        </section>

        {{-- Flotte et journal --}}
        <section class="grid grid-cols-1 gap-gutter md:grid-cols-2">
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline-sm text-headline-sm">Répartition de la flotte</h2>
                    <span class="font-label-md text-label-md text-on-surface-variant">{{ $totalFlotte }} unités</span>
                </div>
                <div class="mt-4 flex items-center gap-6">
                    <div class="relative h-32 w-32 shrink-0 rounded-full" style="background: {{ $anneau }}">
                        <div class="absolute inset-4 flex flex-col items-center justify-center rounded-full bg-surface-container-lowest">
                            <span class="font-headline-md text-headline-md">{{ $totalFlotte }}</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant">véhicules</span>
                        </div>
                    </div>
                    <ul class="space-y-2 font-body-md text-body-md">
                        @foreach (['car' => 'Grands cars', 'tata' => 'Bus Tata', 'minibus' => 'Minibus'] as $type => $libelle)
                            <li class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-sm" style="background: {{ $couleursFlotte[$type] }}"></span>
                                {{ $libelle }} <span class="text-on-surface-variant">({{ $flotte[$type] ?? 0 }})</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                <h2 class="font-headline-sm text-headline-sm">Journal des validations</h2>
                <ul class="mt-4 space-y-3">
                    @forelse ($journal as $entree)
                        <li class="flex items-start gap-3 rounded-lg bg-surface-container-low p-3">
                            <x-ui.icon class="text-primary-container">verified_user</x-ui.icon>
                            <div>
                                <div class="font-label-lg text-label-lg">{{ $entree->libelle }}</div>
                                <div class="font-body-sm text-body-sm text-on-surface-variant">
                                    Par {{ $entree->admin?->name ?? 'un administrateur' }} · {{ $entree->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="font-body-md text-body-md text-on-surface-variant">Aucune action enregistrée pour le moment.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
</x-app-layout>