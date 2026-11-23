@php
    $prenom = \Illuminate\Support\Str::before($user->name, ' ');
    $t = $prochain?->trajet;
    $support = config('transport.support');
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-8 px-4 py-8 sm:px-8">

        {{-- Accueil --}}
        <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-6">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.badge tone="success"><x-ui.icon>verified</x-ui.icon> Compte actif</x-ui.badge>
                        @if ($user->telephone)
                            <span class="inline-flex items-center gap-1 font-label-md text-label-md text-on-surface-variant">
                                <x-ui.icon>smartphone</x-ui.icon> +221 {{ \App\Support\Telephone::formater($user->telephone) }}
                            </span>
                        @endif
                    </div>
                    <h1 class="mt-3 font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">Bonjour {{ $prenom }} 👋</h1>
                    <p class="mt-2 max-w-xl font-body-md text-body-md text-on-surface-variant">
                        Bienvenue dans votre espace voyageur. Réservez vos trajets interurbains en ligne et évitez les files d'attente à la gare.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="route('trajets.index')"><x-ui.icon>add_circle</x-ui.icon> Nouveau voyage</x-ui.button>
                    @if ($dernierBillet)
                        <x-ui.button :href="route('voyageur.reservations.show', $dernierBillet)" variant="light">
                            <x-ui.icon>qr_code_2</x-ui.icon> Dernier e-billet
                        </x-ui.button>
                    @endif
                </div>
            </div>
        </section>

        {{-- Prochain départ --}}
        @if ($prochain)
            <section class="overflow-hidden rounded-xl bg-primary text-on-primary shadow-elevated">
                <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[minmax(0,1fr)_340px]">
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2 font-label-md text-label-md uppercase tracking-wider">
                            <span class="text-secondary-container">● Prochain départ confirmé</span>
                            <span class="text-on-primary-container">Réf. {{ $prochain->reference }}</span>
                        </div>

                        <div class="mt-5 rounded-xl bg-white/10 p-5">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-label-sm text-label-sm uppercase text-on-primary-container">Origine</div>
                                    <div class="font-headline-md text-headline-md">{{ $t->ville_depart }}</div>
                                    <div class="mt-1 inline-flex items-center gap-1 font-label-md text-label-md text-secondary-container">
                                        <x-ui.icon>schedule</x-ui.icon> {{ substr($t->heure_depart, 0, 5) }}
                                    </div>
                                </div>
                                <div class="hidden flex-1 items-center gap-2 sm:flex">
                                    <span class="h-2 w-2 rounded-full bg-secondary-container"></span>
                                    <span class="h-px flex-1 bg-white/30"></span>
                                    <x-ui.icon class="text-secondary-container">directions_bus</x-ui.icon>
                                    <span class="h-px flex-1 bg-white/30"></span>
                                    <span class="h-2 w-2 rounded-full bg-white"></span>
                                </div>
                                <div class="text-right">
                                    <div class="font-label-sm text-label-sm uppercase text-on-primary-container">Destination</div>
                                    <div class="font-headline-md text-headline-md">{{ $t->ville_arrivee }}</div>
                                </div>
                            </div>
                        </div>

                        <p class="mt-4 font-body-md text-body-md text-on-primary-container">
                            Départ
                            {{ $t->depart_le->isToday() ? "aujourd'hui" : ($t->depart_le->isTomorrow() ? 'demain' : $t->date_depart->translatedFormat('l d F')) }}
                            à {{ substr($t->heure_depart, 0, 5) }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-surface-container-lowest p-5 text-on-surface">
                        <dl class="space-y-2 font-body-md text-body-md">
                            <div class="flex justify-between gap-3"><dt class="text-on-surface-variant">Siège(s)</dt><dd class="font-label-lg text-label-lg">{{ $prochain->sieges->pluck('numero')->sort()->implode(', ') }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-on-surface-variant">Transporteur</dt><dd class="font-label-lg text-label-lg">{{ $t->transporteur->nom_compagnie ?? 'Transporteur' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-on-surface-variant">Véhicule</dt><dd class="font-label-lg text-label-lg">{{ ucfirst($t->vehicule->type_vehicule) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-on-surface-variant">Montant réglé</dt><dd class="font-label-lg text-label-lg text-primary-container">{{ number_format($prochain->montant_total, 0, ',', ' ') }} FCFA</dd></div>
                        </dl>
                        <x-ui.button :href="route('voyageur.reservations.show', $prochain)" class="mt-4 w-full">
                            <x-ui.icon>qr_code_2</x-ui.icon> Afficher le billet
                        </x-ui.button>
                    </div>
                </div>
            </section>
        @endif

        {{-- Recherche --}}
        <section>
            <p class="inline-flex items-center gap-1 font-label-md text-label-md uppercase tracking-wider text-primary-container">
                <x-ui.icon>travel_explore</x-ui.icon> Planification express
            </p>
            <h2 class="mt-1 font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">Rechercher un trajet disponible</h2>

            <form method="GET" action="{{ route('trajets.index') }}"
                  class="mt-4 grid grid-cols-1 items-end gap-4 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <x-input-label for="depart" value="Ville de départ" />
                    <x-text-input id="depart" name="depart" type="text" list="villes" class="mt-1" placeholder="Dakar" />
                </div>
                <div>
                    <x-input-label for="arrivee" value="Ville d'arrivée" />
                    <x-text-input id="arrivee" name="arrivee" type="text" list="villes" class="mt-1" placeholder="Saint-Louis" />
                </div>
                <div>
                    <x-input-label for="date" value="Date du voyage" />
                    <x-text-input id="date" name="date" type="date" min="{{ now()->toDateString() }}" class="mt-1" />
                </div>
                <x-primary-button>Trouver <x-ui.icon>arrow_forward</x-ui.icon></x-primary-button>
            </form>

            <datalist id="villes">
                @foreach (config('transport.villes', []) as $ville)
                    <option value="{{ $ville }}"></option>
                @endforeach
            </datalist>

            @if ($populaires->isNotEmpty())
                <div class="mt-3 flex flex-wrap items-center gap-2 rounded-xl bg-surface-container-low p-3 font-body-sm text-body-sm">
                    <span class="font-label-md text-label-md text-on-surface-variant">Lignes les plus demandées :</span>
                    @foreach ($populaires as $ligne)
                        <a href="{{ route('trajets.index', ['depart' => $ligne->ville_depart, 'arrivee' => $ligne->ville_arrivee]) }}"
                           class="rounded-lg bg-surface-container-lowest px-3 py-1 text-on-surface hover:text-primary-container">
                            {{ $ligne->ville_depart }} → {{ $ligne->ville_arrivee }}
                            <span class="font-label-md text-label-md text-primary-container">{{ number_format($ligne->prix_min, 0, ',', ' ') }} F</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Statistiques --}}
        <section class="grid grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['directions_bus', 'bg-primary-fixed text-on-primary-fixed-variant', 'Voyages effectués', $stats['voyages'] . ' voyage(s)', 'Départs terminés'],
                ['hourglass_top', 'bg-secondary-fixed text-on-secondary-fixed-variant', 'Réservations en attente', $stats['en_attente'] . ' en attente', 'À payer rapidement'],
                ['confirmation_number', 'bg-tertiary-fixed text-on-tertiary-fixed-variant', 'Départs à venir', $stats['a_venir'] . ' confirmé(s)', 'Billets prêts'],
                ['payments', 'bg-primary-fixed text-on-primary-fixed-variant', 'Total réglé', number_format($stats['total'], 0, ',', ' ') . ' FCFA', 'Réservations confirmées'],
            ] as [$icone, $couleur, $libelle, $valeur, $detail])
                <div class="flex items-center gap-4 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg {{ $couleur }}">
                        <x-ui.icon>{{ $icone }}</x-ui.icon>
                    </div>
                    <div>
                        <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $libelle }}</div>
                        <div class="font-headline-sm text-headline-sm">{{ $valeur }}</div>
                        <div class="font-label-sm text-label-sm text-on-surface-variant">{{ $detail }}</div>
                    </div>
                </div>
            @endforeach
        </section>

        {{-- Historique --}}
        <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-headline-md text-headline-md">Historique et trajets récents</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">Consultez vos réservations et retrouvez vos e-billets.</p>
                </div>
                <a href="{{ route('voyageur.reservations.index') }}" class="font-label-lg text-label-lg text-primary-container hover:underline">Voir tout l'historique →</a>
            </div>
            <div class="mt-4">
                @include('voyageur.reservations._table', ['reservations' => $historique])
            </div>
        </section>

        {{-- Informations --}}
        <section class="grid grid-cols-1 gap-gutter lg:grid-cols-2">
            <div class="flex items-start gap-4 rounded-xl bg-surface-container-low p-6">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-surface-container-lowest text-primary-container">
                    <x-ui.icon>verified_user</x-ui.icon>
                </div>
                <div>
                    <h3 class="font-headline-sm text-headline-sm">Annulation et remboursement</h3>
                    <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
                        Votre place est annulable jusqu'à {{ \App\Models\Reservation::DELAI_ANNULATION_HEURES }} heures avant le départ.
                        Les sièges d'une réservation non payée sont libérés après {{ \App\Models\Reservation::DELAI_EXPIRATION_MINUTES }} minutes.
                    </p>
                </div>
            </div>

            @if (! empty($support['telephone']))
                <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                    <h3 class="inline-flex items-center gap-2 font-headline-sm text-headline-sm"><x-ui.icon class="text-primary-container">support_agent</x-ui.icon> Assistance voyageur</h3>
                    <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Une question sur votre trajet ou un retard ?</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <x-ui.button :href="'tel:' . preg_replace('/\s+/', '', $support['telephone'])" variant="light"><x-ui.icon>call</x-ui.icon> {{ $support['telephone'] }}</x-ui.button>
                        @if (! empty($support['whatsapp']))
                            <x-ui.button :href="'https://wa.me/' . $support['whatsapp']"><x-ui.icon>chat</x-ui.icon> WhatsApp</x-ui.button>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>