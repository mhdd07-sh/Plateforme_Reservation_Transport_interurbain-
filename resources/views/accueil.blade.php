@php
    $urlRecherche = fn (array $params = []) => Route::has('trajets.index') ? route('trajets.index', $params) : '#';
    $connecte = auth()->check();
@endphp

<x-app-layout>
    {{-- Hero --}}
    <section class="text-on-primary" style="background-image: linear-gradient(135deg, #0d5c3a 0%, #164f36 55%, #226b47 100%)">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8 sm:py-20">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 font-label-sm text-label-sm uppercase tracking-wider text-on-primary-container">
                <span class="h-1.5 w-1.5 rounded-full bg-secondary-container"></span>
                Tata · Minibus · Cars · 7 places
            </span>

            <h1 class="mt-5 max-w-3xl font-display-hero-mobile text-display-hero-mobile sm:font-display-hero sm:text-display-hero">
                Réservez ou proposez un trajet interurbain, sans file d'attente
            </h1>
            <p class="mt-4 max-w-2xl font-body-lg text-body-lg text-on-primary-container">
                Une plateforme moderne pour les voyageurs, les transporteurs particuliers et les compagnies de transport du Sénégal.
            </p>

            <div class="mt-7 flex flex-wrap gap-3">
                @if ($connecte)
                    <x-ui.button :href="$urlRecherche()" variant="accent">Rechercher un trajet</x-ui.button>
                    <x-ui.button :href="route('dashboard')" variant="outline-light">Mon espace</x-ui.button>
                @else
                    <x-ui.button :href="route('register')" variant="accent">Créer un compte</x-ui.button>
                    <x-ui.button :href="route('login')" variant="outline-light">Connexion</x-ui.button>
                @endif
            </div>


            <datalist id="villes">
                @foreach (config('transport.villes', []) as $ville)
                    <option value="{{ $ville }}"></option>
                @endforeach
            </datalist>
        </div>
    </section>

    {{-- Fonctionnalités --}}
    <section id="fonctionnalites" class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8">
        <p class="font-label-md text-label-md uppercase tracking-wider text-primary-container">Solutions complètes</p>
        <h2 class="mt-1 font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">Fonctionnalités</h2>

        <div class="mt-8 grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['search', 'Rechercher un trajet', "Départ, arrivée, date : trouvez les trajets disponibles des compagnies et des transporteurs particuliers."],
                ['event_seat', 'Choisir sa place', 'Visualisez le plan du véhicule et réservez votre siège en temps réel.'],
                ['payments', 'Payer à distance', "Wave, Orange Money ou carte bancaire, sans passer par la gare ni faire la queue au guichet."],
                ['qr_code_2', 'Billet électronique', 'Recevez votre billet avec QR code et retrouvez-le dans votre historique de réservations.'],
                ['alt_route', 'Publier un trajet', 'Les transporteurs particuliers proposent un trajet régulier ou occasionnel et suivent les places restantes.'],
                ['directions_bus', 'Gérer sa flotte', 'Les compagnies gèrent véhicules, sièges, départs et réservations depuis un tableau de bord dédié.'],
            ] as $i => [$icone, $titre, $texte])
                <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-secondary-container text-on-secondary-container">
                        <x-ui.icon>{{ $icone }}</x-ui.icon>
                    </div>
                    <h3 class="mt-4 font-headline-sm text-headline-sm">{{ $titre }}</h3>
                    <p class="mt-2 font-body-md text-body-md text-on-surface-variant">{{ $texte }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Couverture du réseau (chiffres réels) --}}
    <section class="bg-surface-container-low">
        <div class="mx-auto grid max-w-[1200px] gap-8 px-4 py-14 sm:px-8 lg:grid-cols-2">
            <div>
                <p class="font-label-md text-label-md uppercase tracking-wider text-secondary">Couverture du réseau</p>
                <h2 class="mt-1 font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">
                    Des départs entre les grandes villes du pays
                </h2>
                <p class="mt-3 font-body-lg text-body-lg text-on-surface-variant">
                    Dakar, Thiès, Mbour, Saint-Louis, Touba, Kaolack, Ziguinchor : consultez les départs à venir et réservez votre siège.
                </p>

                <div class="mt-6 grid grid-cols-3 gap-3">
                    @foreach ([
                        [$stats['trajets'], 'Départs à venir'],
                        [$stats['compagnies'], 'Transporteurs'],
                        [$stats['villes'], 'Villes desservies'],
                    ] as [$valeur, $libelle])
                        <div class="rounded-xl bg-surface-container-lowest p-4 text-center shadow-card">
                            <div class="font-headline-md text-headline-md text-primary-container">{{ $valeur }}</div>
                            <div class="mt-1 font-label-sm text-label-sm text-on-surface-variant">{{ $libelle }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                <h3 class="font-headline-sm text-headline-sm">Trajets populaires</h3>

                @forelse ($populaires as $ligne)
                    <a href="{{ $urlRecherche(['depart' => $ligne->ville_depart, 'arrivee' => $ligne->ville_arrivee]) }}"
                       class="mt-3 flex items-center justify-between rounded-lg border border-outline-variant/40 px-4 py-3 transition hover:border-primary-container">
                        <div>
                            <div class="font-label-lg text-label-lg">{{ $ligne->ville_depart }} → {{ $ligne->ville_arrivee }}</div>
                            <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $ligne->nombre }} départ(s)</div>
                        </div>
                        <div class="text-right font-label-lg text-label-lg text-primary-container">
                            dès {{ number_format($ligne->prix_min, 0, ',', ' ') }} FCFA
                        </div>
                    </a>
                @empty
                    <p class="mt-3 font-body-md text-body-md text-on-surface-variant">Aucun trajet n'est publié pour le moment.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Comment ça marche --}}
    <section class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8">
        <p class="font-label-md text-label-md uppercase tracking-wider text-primary-container">Simplicité totale</p>
        <h2 class="mt-1 font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">Comment ça marche</h2>

        <div class="mt-8 grid grid-cols-1 gap-gutter md:grid-cols-3">
            @foreach ([
                ['Créez votre compte', 'Indiquez si vous êtes voyageur, transporteur particulier ou compagnie, avec votre numéro de téléphone.'],
                ['Trouvez ou publiez un trajet', 'Les voyageurs recherchent leur trajet, les transporteurs publient leurs départs avec horaires et tarifs.'],
                ['Réservez et voyagez', 'Choisissez votre siège, payez en ligne avec Wave ou Orange Money et recevez votre billet.'],
            ] as $i => [$titre, $texte])
                <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-secondary-container font-label-lg text-label-lg text-on-secondary-container">{{ $i + 1 }}</div>
                    <h3 class="mt-4 font-headline-sm text-headline-sm">{{ $titre }}</h3>
                    <p class="mt-2 font-body-md text-body-md text-on-surface-variant">{{ $texte }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Profils --}}
    <section id="profils" class="bg-surface-container-low">
        <div class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8">
            <p class="font-label-md text-label-md uppercase tracking-wider text-primary-container">Un espace adapté à chaque profil</p>
            <h2 class="mt-1 font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">Qui êtes-vous ?</h2>

            <div class="mt-8 grid grid-cols-1 gap-gutter md:grid-cols-3">
                @foreach ([
                    ['voyageur', 'airline_seat_recline_normal', 'Voyageur', "Réservez une place chez une compagnie ou chez un transporteur particulier, choisissez votre siège et voyagez l'esprit tranquille."],
                    ['particulier', 'directions_car', 'Transporteur particulier', 'Publiez vos trajets réguliers ou de week-end. Une pièce d\'identité vous sera demandée pour la sécurité de tous.'],
                    ['compagnie', 'apartment', 'Compagnie', 'Gérez véhicules, trajets, départs et réservations en temps réel depuis votre tableau de bord dédié.'],
                ] as [$type, $icone, $titre, $texte])
                    <div class="flex flex-col rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-fixed text-on-primary-fixed-variant">
                            <x-ui.icon>{{ $icone }}</x-ui.icon>
                        </div>
                        <h3 class="mt-4 font-headline-md text-headline-md">{{ $titre }}</h3>
                        <p class="mt-2 flex-1 font-body-md text-body-md text-on-surface-variant">{{ $texte }}</p>
                        <x-ui.button :href="route('register', ['type' => $type])" variant="outline" class="mt-6 w-full">Créer un compte</x-ui.button>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Bandeau final --}}
    <section class="mx-auto max-w-[1200px] px-4 py-14 sm:px-8">
        <div class="flex flex-col gap-6 rounded-xl bg-primary p-8 text-on-primary shadow-elevated md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">Prêt à voyager ?</h2>
                <p class="mt-2 max-w-xl font-body-md text-body-md text-on-primary-container">
                    Parcourez les départs disponibles ou créez votre compte en quelques secondes.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button :href="$urlRecherche()" variant="light">Voir tous les trajets</x-ui.button>
                @unless ($connecte)
                    <x-ui.button :href="route('register')" variant="accent">Créer un compte</x-ui.button>
                @endunless
            </div>
        </div>
    </section>
</x-app-layout>