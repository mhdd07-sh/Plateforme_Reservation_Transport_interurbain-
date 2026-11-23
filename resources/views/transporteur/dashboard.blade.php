@php
    $nomCompagnie = $transporteur?->nom_compagnie ?? auth()->user()->name;
    $support = config('transport.support');

    $notePaiement = fn ($r) => match ($r->statut) {
        'confirmee' => 'Payé' . ($r->operateur_paiement ? ' via ' . \App\Models\Paiement::libelleMoyen($r->operateur_paiement) : ''),
        'en_attente' => $r->expire_at ? 'Délai expire à ' . $r->expire_at->format('H:i') : 'En attente de paiement',
        'expiree' => 'Délai de paiement dépassé',
        default => 'Réservation annulée',
    };
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-8 px-4 py-8 sm:px-8">

        {{-- Profil incomplet --}}
        @if (! $transporteur)
            <section class="rounded-xl bg-secondary-fixed/50 p-6 font-body-md text-body-md text-on-secondary-fixed">
                Votre profil transporteur n'est pas encore créé. Contactez l'administrateur.
            </section>

        {{-- En attente de validation --}}
        @elseif ($transporteur->statut === \App\Models\Transporteur::STATUT_EN_ATTENTE)
            <section class="flex flex-wrap items-start gap-4 rounded-xl border border-secondary-container bg-secondary-fixed/40 p-6">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-secondary-container text-on-secondary-container">
                    <x-ui.icon>hourglass_top</x-ui.icon>
                </div>
                <div class="flex-1">
                    <x-ui.badge tone="warning">Validation en cours</x-ui.badge>
                    <h1 class="mt-2 font-headline-md text-headline-md">Votre compte est en attente de validation</h1>
                    <p class="mt-1 max-w-2xl font-body-md text-body-md text-on-surface-variant">
                        Votre compte est en attente de validation par l'administrateur. Vous pourrez gérer vos véhicules et vos trajets dès sa validation.
                    </p>
                </div>
                <x-ui.badge>
                    Compte {{ $transporteur->type === \App\Models\Transporteur::TYPE_PARTICULIER ? 'particulier' : 'compagnie' }}
                </x-ui.badge>
            </section>

        {{-- Suspendu --}}
        @elseif (! $transporteur->estValide())
            <section class="flex flex-wrap items-start gap-4 rounded-xl border border-error/30 bg-error-container/60 p-6">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-error text-on-error">
                    <x-ui.icon>block</x-ui.icon>
                </div>
                <div class="flex-1">
                    <x-ui.badge tone="danger">Accès restreint</x-ui.badge>
                    <h1 class="mt-2 font-headline-md text-headline-md text-on-error-container">
                        {{ $transporteur->statut === \App\Models\Transporteur::STATUT_REFUSE ? "Votre demande n'a pas été retenue" : 'Votre compte est suspendu' }}
                    </h1>
                    <p class="mt-1 max-w-2xl font-body-md text-body-md text-on-error-container">
                        Veuillez contacter l'équipe administrative pour régulariser votre situation.
                    </p>
                </div>
                @if (! empty($support['telephone']))
                    <x-ui.button :href="'tel:' . preg_replace('/\s+/', '', $support['telephone'])" variant="danger">
                        <x-ui.icon>call</x-ui.icon> Contacter l'administration
                    </x-ui.button>
                @endif
            </section>

        {{-- Compte actif --}}
        @else
            <x-page-header titre="Espace transporteur" :soustitre="'Bienvenue, ' . $nomCompagnie . '.'" surtitre="Tableau de bord">
                <x-ui.button :href="route('transporteur.vehicules.create')" variant="light"><x-ui.icon>add</x-ui.icon> Ajouter un véhicule</x-ui.button>
                <x-ui.button :href="route('transporteur.trajets.create')"><x-ui.icon>add_circle</x-ui.icon> Créer un trajet</x-ui.button>
            </x-page-header>

            {{-- Chiffres clés --}}
            <section class="grid grid-cols-1 gap-gutter md:grid-cols-3">
                <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <span class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Véhicules</span>
                        <x-ui.icon class="text-primary-container">directions_bus</x-ui.icon>
                    </div>
                    <div class="mt-2 font-display-hero-mobile text-display-hero-mobile">{{ $stats['vehicules'] }}</div>
                    <div class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ $stats['repartition'] ?: 'Aucun véhicule' }}</div>
                    @if ($stats['vehicules'] > 0)
                        <div class="mt-2 font-label-sm text-label-sm text-primary-container">
                            {{ (int) round($stats['actifs'] / $stats['vehicules'] * 100) }}% actifs
                        </div>
                    @endif
                </div>

                <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <span class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Trajets publiés</span>
                        <x-ui.icon class="text-primary-container">alt_route</x-ui.icon>
                    </div>
                    <div class="mt-2 font-display-hero-mobile text-display-hero-mobile">{{ $stats['trajets_publies'] }}</div>
                    <div class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ $stats['departs_aujourdhui'] }} départ(s) aujourd'hui</div>
                </div>

                <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <span class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Réservations confirmées</span>
                        <x-ui.icon class="text-primary-container">confirmation_number</x-ui.icon>
                    </div>
                    <div class="mt-2 font-display-hero-mobile text-display-hero-mobile">{{ $stats['confirmees'] }}</div>
                    <div class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ number_format($stats['revenus'], 0, ',', ' ') }} FCFA générés</div>
                    @if ($stats['confirmees'] > 0)
                        <div class="font-label-sm text-label-sm text-primary-container">Moy. {{ number_format($stats['moyenne'], 0, ',', ' ') }} FCFA / réservation</div>
                    @endif
                </div>
            </section>

            {{-- Prochains départs --}}
            <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="font-headline-md text-headline-md">Prochains départs</h2>
                        <p class="font-body-md text-body-md text-on-surface-variant">{{ $prochains->count() }} départ(s) programmé(s)</p>
                    </div>
                    <a href="{{ route('transporteur.trajets.index') }}" class="font-label-lg text-label-lg text-primary-container hover:underline">Gérer le calendrier →</a>
                </div>

                @if ($prochains->isEmpty())
                    <div class="mt-6 rounded-xl bg-surface-container-low p-8 text-center">
                        <x-ui.icon class="text-outline">event_busy</x-ui.icon>
                        <h3 class="mt-2 font-headline-sm text-headline-sm">Aucun départ programmé</h3>
                        <p class="mx-auto mt-1 max-w-md font-body-md text-body-md text-on-surface-variant">
                            Vous n'avez actuellement aucun trajet planifié. Créez un itinéraire pour ouvrir les réservations aux passagers.
                        </p>
                        <x-ui.button :href="route('transporteur.trajets.create')" class="mt-4">Créer un trajet</x-ui.button>
                    </div>
                @else
                    <div class="mt-4">
                        @include('transporteur.trajets._table', ['trajets' => $prochains])
                    </div>
                @endif
            </section>

            {{-- Dernières réservations --}}
            <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                <h2 class="font-headline-md text-headline-md">Dernières réservations</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Suivi des billets réservés sur vos trajets.</p>

                @if ($dernieres->isEmpty())
                    <div class="mt-6 rounded-xl bg-surface-container-low p-8 text-center">
                        <x-ui.icon class="text-outline">confirmation_number</x-ui.icon>
                        <h3 class="mt-2 font-headline-sm text-headline-sm">Aucune réservation pour le moment</h3>
                        <p class="mx-auto mt-1 max-w-md font-body-md text-body-md text-on-surface-variant">
                            Les réservations effectuées par les voyageurs apparaîtront ici avec le trajet, les sièges et le paiement.
                        </p>
                    </div>
                @else
                    <ul class="mt-4 divide-y divide-outline-variant/30">
                        @foreach ($dernieres as $r)
                            <li class="flex flex-wrap items-center justify-between gap-4 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-fixed font-label-lg text-label-lg text-on-primary-fixed-variant">
                                        {{ mb_strtoupper(mb_substr($r->user->name, 0, 1)) }}{{ mb_strtoupper(mb_substr(\Illuminate\Support\Str::after($r->user->name . ' ', ' '), 0, 1)) }}
                                    </span>
                                    <div>
                                        <div class="font-label-lg text-label-lg">{{ $r->user->name }}</div>
                                        <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $r->reference }} · {{ $r->trajet->ville_depart }} → {{ $r->trajet->ville_arrivee }}</div>
                                    </div>
                                </div>
                                <div class="font-body-md text-body-md text-on-surface-variant">
                                    {{ $r->nombre_places }} place(s)
                                    @if ($r->sieges->isNotEmpty())
                                        (sièges {{ $r->sieges->pluck('numero')->sort()->map(fn ($n) => str_pad($n, 2, '0', STR_PAD_LEFT))->implode(', ') }})
                                    @endif
                                </div>
                                <div class="text-right">
                                    <div class="font-label-lg text-label-lg">{{ number_format($r->montant_total, 0, ',', ' ') }} FCFA</div>
                                    <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $notePaiement($r) }}</div>
                                </div>
                                <x-ui.badge :tone="$r->toneStatut()">{{ $r->libelleStatut() }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif
    </div>
</x-app-layout>