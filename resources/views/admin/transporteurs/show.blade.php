@php
    $bouton = 'inline-flex items-center gap-1 rounded-lg px-4 py-2.5 font-label-lg text-label-lg transition';
    $maxMois = max(1, (int) $mois->max('total'));
    $court = fn (int $n) => $n >= 1000000 ? number_format($n / 1000000, 1, ',', ' ') . 'M' : ($n >= 1000 ? number_format($n / 1000, 0, ',', ' ') . 'k' : (string) $n);
    $onglets = ['vehicules' => ['Véhicules', $stats['vehicules']], 'trajets' => ['Trajets', $trajets->count()], 'historique' => ['Historique', $journal->count()]];
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <a href="{{ route('admin.transporteurs.index') }}" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container hover:underline">
            <x-ui.icon>arrow_back</x-ui.icon> Retour aux transporteurs
        </a>

        @if (session('mot_de_passe_initial'))
            <section class="rounded-xl border border-secondary-container bg-secondary-fixed/40 p-5">
                <div class="flex items-start gap-3">
                    <x-ui.icon class="text-on-secondary-container">key</x-ui.icon>
                    <div>
                        <h2 class="font-headline-sm text-headline-sm">Mot de passe initial</h2>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            Communiquez-le au transporteur : il <strong>ne sera plus affiché</strong> après cette page.
                        </p>
                        <code class="mt-2 inline-block rounded-md bg-surface-container-lowest px-3 py-2 font-label-lg text-label-lg">{{ session('mot_de_passe_initial') }}</code>
                    </div>
                </div>
            </section>
        @endif

        {{-- En-tête --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">{{ $transporteur->nomAffiche() }}</h1>
                <x-ui.badge :tone="$transporteur->toneStatut()">{{ $transporteur->libelleStatut() }}</x-ui.badge>
                @if ($transporteur->numero_agrement)
                    <code class="rounded-md bg-surface-container px-2 py-1 font-label-md text-label-md">{{ $transporteur->numero_agrement }}</code>
                @endif
            </div>
            <div class="flex flex-wrap gap-3">
                @if ($transporteur->statut === 'en_attente')
                    <form method="POST" action="{{ route('admin.transporteurs.valider', $transporteur) }}">@csrf
                        <button type="submit" class="{{ $bouton }} bg-primary-container text-on-primary hover:bg-primary"><x-ui.icon>check_circle</x-ui.icon> Valider le transporteur</button>
                    </form>
                    <form method="POST" action="{{ route('admin.transporteurs.refuser', $transporteur) }}" onsubmit="return confirm('Refuser cette demande ?')">@csrf
                        <button type="submit" class="{{ $bouton }} border border-outline-variant text-on-surface-variant hover:bg-surface-container-low"><x-ui.icon>cancel</x-ui.icon> Refuser</button>
                    </form>
                @elseif ($transporteur->statut === 'valide')
                    <form method="POST" action="{{ route('admin.transporteurs.suspendre', $transporteur) }}" onsubmit="return confirm('Suspendre ce transporteur ? Ses trajets ne seront plus visibles des voyageurs. Les réservations existantes restent valides.')">@csrf
                        <button type="submit" class="{{ $bouton }} bg-error-container text-on-error-container hover:opacity-80"><x-ui.icon>block</x-ui.icon> Suspendre le transporteur</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.transporteurs.reactiver', $transporteur) }}">@csrf
                        <button type="submit" class="{{ $bouton }} bg-primary-container text-on-primary hover:bg-primary"><x-ui.icon>play_arrow</x-ui.icon> Réactiver le transporteur</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid gap-gutter lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)]">
            {{-- Fiche --}}
            <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                <div class="flex items-center gap-4">
                    <x-avatar :nom="$transporteur->nomAffiche()" tone="dark" class="!h-14 !w-14 !text-headline-sm" />
                    <div>
                        <div class="font-headline-sm text-headline-sm">{{ $transporteur->nomAffiche() }}</div>
                        <x-ui.badge :tone="$transporteur->type === 'compagnie' ? 'info' : 'warning'">{{ $transporteur->libelleType() }}</x-ui.badge>
                    </div>
                </div>

                <dl class="mt-5 space-y-2 font-body-md text-body-md">
                    @foreach ([
                        ['Responsable', $transporteur->user->name],
                        ['Téléphone', '+221 ' . \App\Support\Telephone::formater($transporteur->user->telephone)],
                        ['E-mail', $transporteur->user->email],
                        ['N° d\'agrément', $transporteur->numero_agrement ?? 'Non applicable'],
                        ['Inscrit le', $transporteur->created_at->translatedFormat('d F Y')],
                        ['Compte de connexion', $transporteur->user->actif ? 'Actif' : 'Désactivé'],
                    ] as [$libelle, $valeur])
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-surface-container-low px-3 py-2.5">
                            <dt class="text-on-surface-variant">{{ $libelle }}</dt>
                            <dd class="text-right font-label-lg text-label-lg">{{ $valeur }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <div class="space-y-gutter">
                <section class="grid grid-cols-1 gap-gutter sm:grid-cols-3">
                    <x-stat-card label="Véhicules" :valeur="$stats['vehicules']" icone="directions_bus" tone="neutral"
                        :detail="$stats['actifs'] . ' actifs · ' . $stats['maintenance'] . ' en maintenance'" />
                    <x-stat-card label="Trajets publiés" :valeur="$stats['publies']" icone="alt_route" tone="warning" detail="À venir" />
                    <x-stat-card label="Réservations" :valeur="$stats['confirmees']" icone="confirmation_number" tone="info" detail="Confirmées" />
                </section>

                <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                    <p class="font-label-md text-label-md uppercase tracking-wider text-primary-container">Volume financier</p>
                    <h2 class="font-headline-md text-headline-md">Chiffre d'affaires généré</h2>
                    <div class="mt-2 font-display-hero-mobile text-display-hero-mobile text-primary-container">
                        {{ number_format($stats['chiffre_affaires'], 0, ',', ' ') }} FCFA
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Réservations confirmées, tous moyens de paiement.</p>

                    <div class="mt-5 flex h-32 items-end gap-3">
                        @foreach ($mois as $m)
                            <div class="flex flex-1 flex-col items-center gap-1">
                                <span class="font-label-sm text-label-sm text-on-surface-variant">{{ $court($m['total']) }}</span>
                                <div class="w-full rounded-t-md bg-primary-container" style="height: {{ max(4, round($m['total'] / $maxMois * 80)) }}px"></div>
                                <span class="font-label-sm text-label-sm">{{ $m['libelle'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>

        {{-- Onglets --}}
        <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
            <div class="flex flex-wrap gap-2 border-b border-outline-variant/40 pb-3">
                @foreach ($onglets as $cle => [$libelle, $nombre])
                    <a href="{{ route('admin.transporteurs.show', [$transporteur, 'onglet' => $cle]) }}"
                       class="rounded-lg px-3 py-2 font-label-lg text-label-lg {{ $onglet === $cle ? 'bg-primary-container text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                        {{ $libelle }} <span class="opacity-70">({{ $nombre }})</span>
                    </a>
                @endforeach
            </div>

            <div class="mt-4 overflow-x-auto">
                @if ($onglet === 'vehicules')
                    <table class="min-w-full text-left">
                        <thead class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
                            <tr><th class="px-4 py-3">Immatriculation</th><th class="px-4 py-3">Marque</th><th class="px-4 py-3">Type et capacité</th><th class="px-4 py-3">Statut</th></tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/30 font-body-md text-body-md">
                            @forelse ($vehicules as $v)
                                <tr class="{{ $v->estActif() ? '' : 'bg-secondary-fixed/20' }}">
                                    <td class="px-4 py-3"><code class="rounded-md bg-surface-container px-2 py-1 font-label-md text-label-md">{{ $v->immatriculation }}</code></td>
                                    <td class="px-4 py-3">{{ $v->marque }}</td>
                                    <td class="px-4 py-3">{{ \App\Models\Vehicule::libelleType($v->type_vehicule) }} ({{ $v->capacite }} places)</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge :tone="$v->estActif() ? 'success' : 'warning'">{{ $v->estActif() ? 'Opérationnel' : 'En maintenance' }}</x-ui.badge>
                                        @if (! $v->estActif() && $v->note_maintenance)
                                            <div class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ $v->note_maintenance }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-10 text-center text-on-surface-variant">Aucun véhicule enregistré.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @elseif ($onglet === 'trajets')
                    <table class="min-w-full text-left">
                        <thead class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
                            <tr><th class="px-4 py-3">Trajet</th><th class="px-4 py-3">Départ</th><th class="px-4 py-3">Tarif</th><th class="px-4 py-3">Places prises</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/30 font-body-md text-body-md">
                            @forelse ($trajets->take(15) as $t)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 font-label-lg text-label-lg">{{ $t->ville_depart }} → {{ $t->ville_arrivee }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $t->date_depart->translatedFormat('D d M') }}, {{ substr($t->heure_depart, 0, 5) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ number_format($t->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-4 py-3">@include('transporteur._remplissage', ['occupes' => $t->sieges_retenus_count, 'capacite' => $t->vehicule->capacite])</td>
                                    <td class="px-4 py-3"><x-ui.badge :tone="$t->toneStatut()">{{ $t->libelleStatut() }}</x-ui.badge></td>
                                    <td class="px-4 py-3 text-right"><a href="{{ route('admin.trajets.show', $t) }}" class="font-label-lg text-label-lg text-primary-container hover:underline">Détail</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-on-surface-variant">Aucun trajet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <ol class="space-y-5 py-2">
                        @foreach ($journal as $entree)
                            <li class="flex gap-3">
                                <span class="mt-1 h-3 w-3 shrink-0 rounded-full bg-primary-container"></span>
                                <div>
                                    <div class="font-label-lg text-label-lg">{{ $entree->libelle }}</div>
                                    <div class="font-body-sm text-body-sm text-on-surface-variant">
                                        {{ $entree->created_at->translatedFormat('l d F Y à H:i') }} · {{ $entree->admin?->name ?? 'Administrateur' }}
                                    </div>
                                </div>
                            </li>
                        @endforeach
                        <li class="flex gap-3">
                            <span class="mt-1 h-3 w-3 shrink-0 rounded-full bg-outline"></span>
                            <div>
                                <div class="font-label-lg text-label-lg">Compte enregistré sur la plateforme</div>
                                <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $transporteur->created_at->translatedFormat('l d F Y à H:i') }}</div>
                            </div>
                        </li>
                    </ol>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>