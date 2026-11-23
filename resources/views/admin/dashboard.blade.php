@php
    $largeur = 600;
    $hauteur = 200;
    $marge = 12;
    $n = $serie->count();
    $max = max(1, (int) $serie->max('total'));

    $points = $serie->values()->map(function ($p, $i) use ($n, $largeur, $hauteur, $marge, $max) {
        $x = $n > 1 ? $marge + $i * (($largeur - 2 * $marge) / ($n - 1)) : $largeur / 2;
        $y = $hauteur - $marge - ($p['total'] / $max) * ($hauteur - 2 * $marge);

        return round($x, 1) . ',' . round($y, 1);
    });

    $ligne = $points->implode(' ');
    $aire = $marge . ',' . ($hauteur - $marge) . ' ' . $ligne . ' ' . ($largeur - $marge) . ',' . ($hauteur - $marge);
    $totalPeriode = $serie->sum('total');
    $maxLignes = max(1, (int) $lignes->max('total'));
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-8 px-4 py-8 sm:px-8">
        <x-page-header titre="Tableau de bord" surtitre="Système en temps réel"
            soustitre="Vue d'ensemble de la plateforme et suivi de la mobilité interurbaine.">
            @foreach ([7 => '7 jours', 30 => '30 jours', 90 => '90 jours'] as $valeur => $libelle)
                <a href="{{ route('admin.dashboard', ['periode' => $valeur]) }}"
                   class="rounded-lg px-3 py-2 font-label-lg text-label-lg {{ $jours === $valeur ? 'bg-primary-container text-on-primary' : 'border border-outline-variant/40 bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-low' }}">
                    {{ $libelle }}
                </a>
            @endforeach
        </x-page-header>

        {{-- Action requise --}}
        @if ($stats['en_attente'] > 0)
            <section class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-secondary-container bg-secondary-fixed/40 p-5">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-secondary-container text-on-secondary-container">
                        <x-ui.icon>gpp_maybe</x-ui.icon>
                    </div>
                    <div>
                        <x-ui.badge tone="warning">Action requise</x-ui.badge>
                        <h2 class="mt-1 font-headline-sm text-headline-sm">{{ $stats['en_attente'] }} transporteur(s) attendent votre validation</h2>
                        <p class="font-body-md text-body-md text-on-surface-variant">Vérifiez les informations de chaque dossier avant de l'activer.</p>
                    </div>
                </div>
                <x-ui.button :href="route('admin.transporteurs.index', ['statut' => 'en_attente'])">
                    Examiner les demandes <x-ui.icon>arrow_forward</x-ui.icon>
                </x-ui.button>
            </section>
        @endif

        {{-- Chiffres clés --}}
        <section class="grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-3">
            <x-stat-card label="Voyageurs inscrits" :valeur="$stats['voyageurs']" icone="groups" tone="info"
                :detail="'+' . $stats['voyageurs_mois'] . ' ce mois'" />
            <x-stat-card label="Transporteurs validés" :valeur="$stats['transporteurs']" icone="directions_bus"
                :detail="'+' . $stats['transporteurs_mois'] . ' ce mois'" />
            <x-stat-card label="Transporteurs en attente" :valeur="$stats['en_attente']" icone="hourglass_top" tone="warning"
                detail="Dossiers à contrôler" />
            <x-stat-card label="Trajets publiés" :valeur="$stats['trajets_publies']" icone="alt_route"
                :detail="'+' . $stats['trajets_semaine'] . ' cette semaine'" />
            <x-stat-card label="Réservations confirmées" :valeur="$stats['reservations']" icone="confirmation_number" tone="info"
                :detail="'Taux de remplissage ' . $stats['remplissage'] . '%'" />
            <x-stat-card label="Revenus du mois" :valeur="number_format($stats['revenus_mois'], 0, ',', ' ') . ' FCFA'" icone="payments"
                :detail="$stats['evolution'] === null ? 'Pas de comparaison disponible' : (($stats['evolution'] >= 0 ? '+' : '') . $stats['evolution'] . '% vs mois dernier')" />
        </section>

        {{-- Courbe et lignes --}}
        <section class="grid grid-cols-1 gap-gutter lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                <p class="font-label-md text-label-md uppercase tracking-wider text-primary-container">Activité billetterie</p>
                <h2 class="font-headline-md text-headline-md">Réservations des {{ $jours }} derniers jours</h2>

                <div class="mt-4">
                    <svg viewBox="0 0 {{ $largeur }} {{ $hauteur }}" class="h-56 w-full" role="img" aria-label="Courbe des réservations">
                        @foreach ([0, 0.5, 1] as $repere)
                            <line x1="{{ $marge }}" x2="{{ $largeur - $marge }}"
                                  y1="{{ $hauteur - $marge - $repere * ($hauteur - 2 * $marge) }}"
                                  y2="{{ $hauteur - $marge - $repere * ($hauteur - 2 * $marge) }}"
                                  stroke="#d9e3f6" stroke-dasharray="4 4" />
                        @endforeach
                        <polygon points="{{ $aire }}" fill="#a9f3c5" fill-opacity="0.45" />
                        <polyline points="{{ $ligne }}" fill="none" stroke="#0d5c3a" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
                    </svg>
                    <div class="mt-1 flex justify-between font-label-sm text-label-sm text-on-surface-variant">
                        <span>{{ $serie->first()['date']->translatedFormat('d M') }}</span>
                        <span>{{ $serie[(int) floor($n / 2)]['date']->translatedFormat('d M') }}</span>
                        <span>{{ $serie->last()['date']->translatedFormat('d M') }}</span>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-surface-container-low px-4 py-3 font-body-md text-body-md">
                    <span><strong>{{ $totalPeriode }}</strong> réservation(s) confirmée(s) sur la période</span>
                    <span class="text-on-surface-variant">Pic : {{ $max }} par jour</span>
                </div>
            </div>

            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                <p class="font-label-md text-label-md uppercase tracking-wider text-primary-container">Réseau routier</p>
                <h2 class="font-headline-md text-headline-md">Trajets les plus demandés</h2>

                @forelse ($lignes as $i => $ligneDemandee)
                    <div class="mt-4">
                        <div class="flex items-center justify-between font-label-lg text-label-lg">
                            <span><span class="mr-2 text-on-surface-variant">{{ $i + 1 }}</span>{{ $ligneDemandee->ville_depart }} → {{ $ligneDemandee->ville_arrivee }}</span>
                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ $ligneDemandee->total }} réservation(s)</span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-surface-container-high">
                            <div class="h-full rounded-full bg-primary-container" style="width: {{ round($ligneDemandee->total / $maxLignes * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="mt-6 font-body-md text-body-md text-on-surface-variant">Aucune réservation confirmée pour le moment.</p>
                @endforelse
            </div>
        </section>

        {{-- Activité récente --}}
        <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
            <h2 class="font-headline-md text-headline-md">Activité récente</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Événements enregistrés sur la plateforme.</p>

            <ul class="mt-4 divide-y divide-outline-variant/30">
                @forelse ($activite as $evenement)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-fixed text-on-primary-fixed-variant">
                                <x-ui.icon>{{ $evenement['icone'] }}</x-ui.icon>
                            </span>
                            <div>
                                <div class="font-label-lg text-label-lg">{{ $evenement['titre'] }}</div>
                                <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $evenement['detail'] }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-ui.badge :tone="$evenement['badge_tone']">{{ $evenement['badge'] }}</x-ui.badge>
                            <span class="font-body-sm text-body-sm text-on-surface-variant">{{ $evenement['date']->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-on-surface-variant">Aucune activité pour le moment.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>