@php
    $libellesType = ['minibus' => 'Minibus', 'tata' => 'Tata', 'car' => 'Car'];
    $typesChoisis = (array) request('types', []);
    $moinsCher = $trajets->getCollection()->min('prix_unitaire');
    $depart = request('depart');
    $arrivee = request('arrivee');
    $dateChoisie = request('date');

    $parametres = fn (array $extra) => array_filter(
        array_merge(request()->only('depart', 'arrivee', 'date', 'prix_max', 'tri', 'types'), $extra),
        fn ($v) => $v !== null && $v !== ''
    );
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <x-voyageur.etapes :actuelle="1" />

        {{-- Recherche --}}
        <form method="GET" action="{{ route('trajets.index') }}"
              class="space-y-4 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
            <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <x-input-label for="depart" value="Départ" />
                    <x-text-input id="depart" name="depart" type="text" list="villes" class="mt-1" :value="$depart" placeholder="Dakar" />
                </div>
                <div>
                    <x-input-label for="arrivee" value="Arrivée" />
                    <x-text-input id="arrivee" name="arrivee" type="text" list="villes" class="mt-1" :value="$arrivee" placeholder="Saint-Louis" />
                </div>
                <div>
                    <x-input-label for="date" value="Date du voyage" />
                    <x-text-input id="date" name="date" type="date" class="mt-1" :value="$dateChoisie" />
                </div>
                <x-primary-button>Rechercher <x-ui.icon>search</x-ui.icon></x-primary-button>
            </div>

            <div class="flex flex-wrap items-end gap-6 border-t border-outline-variant/30 pt-4">
                <div>
                    <div class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Type de véhicule</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($libellesType as $cle => $libelle)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="types[]" value="{{ $cle }}" class="peer sr-only" @checked(in_array($cle, $typesChoisis, true))>
                                <span class="inline-flex rounded-lg border border-outline-variant/60 px-3 py-1.5 font-label-md text-label-md text-on-surface-variant peer-checked:border-primary-container peer-checked:bg-primary-container peer-checked:text-on-primary">
                                    {{ $libelle }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="w-40">
                    <x-input-label for="prix_max" value="Prix max (FCFA)" />
                    <x-text-input id="prix_max" name="prix_max" type="number" min="0" step="500" class="mt-1" :value="request('prix_max')" />
                </div>
                <div class="w-48">
                    <x-input-label for="tri" value="Trier par" />
                    <select id="tri" name="tri" class="mt-1 block min-h-[48px] w-full rounded-lg border border-[#D1D5DB] bg-white px-4 font-body-md text-body-md focus:border-primary-container focus:ring-2 focus:ring-primary-container/15">
                        <option value="heure" @selected(request('tri', 'heure') === 'heure')>Heure : départ le plus tôt</option>
                        <option value="prix" @selected(request('tri') === 'prix')>Prix : moins cher d'abord</option>
                    </select>
                </div>
            </div>
        </form>

        <datalist id="villes">
            @foreach (config('transport.villes', []) as $ville)
                <option value="{{ $ville }}"></option>
            @endforeach
        </datalist>

        {{-- Bande des jours --}}
        <div class="flex gap-2 overflow-x-auto pb-1">
            @foreach ($bande as $jour)
                @php $choisi = $dateChoisie === $jour['date']->toDateString(); @endphp
                <a href="{{ route('trajets.index', $parametres(['date' => $jour['date']->toDateString(), 'page' => null])) }}"
                   class="min-w-[112px] flex-1 rounded-xl border px-3 py-3 text-center transition {{ $choisi ? 'border-primary-container bg-primary-container text-on-primary shadow-elevated' : 'border-outline-variant/40 bg-surface-container-lowest hover:border-primary-container' }}">
                    <div class="font-label-md text-label-md {{ $choisi ? 'text-on-primary-container' : 'text-on-surface-variant' }}">{{ $jour['date']->translatedFormat('D d M') }}</div>
                    <div class="mt-1 font-label-lg text-label-lg">
                        {{ $jour['prix'] ? 'Dès ' . number_format($jour['prix'], 0, ',', ' ') . ' F' : '—' }}
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Résultats --}}
        <div>
            <p class="font-label-md text-label-md uppercase tracking-wider text-primary-container">
                @if ($depart && $arrivee) {{ $depart }} → {{ $arrivee }} @else Tous les départs @endif
            </p>
            <h1 class="mt-1 font-headline-md text-headline-md">{{ $trajets->total() }} départ(s) disponible(s)</h1>
        </div>

        @if ($errors->any())
            <div class="rounded-lg bg-error-container px-4 py-3 font-body-md text-body-md text-on-error-container">{{ $errors->first() }}</div>
        @endif

        <div class="space-y-4">
            @forelse ($trajets as $trajet)
                @php
                    $libres = max(0, $trajet->vehicule->capacite - $trajet->sieges_retenus_count);
                    $complet = $libres === 0;
                @endphp

                <article class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card transition hover:shadow-elevated">
                    <div class="grid items-center gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_auto]">
                        <div>
                            @if ($moinsCher !== null && $trajet->prix_unitaire === $moinsCher && $trajets->total() > 1)
                                <x-ui.badge tone="success" class="mb-2">Prix le plus bas</x-ui.badge>
                            @endif
                            <h2 class="font-headline-sm text-headline-sm">{{ $trajet->transporteur->nom_compagnie ?? 'Transporteur' }}</h2>
                            <p class="font-body-md text-body-md text-on-surface-variant">
                                {{ ucfirst($trajet->vehicule->type_vehicule) }} · {{ $trajet->vehicule->marque }} · {{ $trajet->vehicule->capacite }} places
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <div>
                                <div class="font-headline-md text-headline-md">{{ substr($trajet->heure_depart, 0, 5) }}</div>
                                <div class="font-label-lg text-label-lg">{{ $trajet->ville_depart }}</div>
                            </div>
                            <div class="flex flex-1 items-center gap-2 text-outline">
                                <span class="h-2 w-2 rounded-full bg-primary-container"></span>
                                <span class="h-px flex-1 bg-outline-variant"></span>
                                <x-ui.icon>directions_bus</x-ui.icon>
                                <span class="h-px flex-1 bg-outline-variant"></span>
                                <span class="h-2 w-2 rounded-full bg-secondary-container"></span>
                            </div>
                            <div class="text-right">
                                <div class="font-label-lg text-label-lg">{{ $trajet->ville_arrivee }}</div>
                                <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $trajet->date_depart->translatedFormat('D d M') }}</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 lg:flex-col lg:items-end">
                            <div class="text-right">
                                <div class="font-headline-md text-headline-md text-primary-container">
                                    {{ number_format($trajet->prix_unitaire, 0, ',', ' ') }} <span class="font-label-md text-label-md">FCFA</span>
                                </div>
                                <div class="font-label-sm text-label-sm {{ $complet ? 'text-error' : ($libres <= 3 ? 'text-secondary' : 'text-on-surface-variant') }}">
                                    {{ $complet ? 'Complet' : ($libres <= 3 ? $libres . ' place(s) restante(s) !' : $libres . ' places libres') }}
                                </div>
                            </div>
                            @if ($complet)
                                <x-ui.button :href="route('trajets.show', $trajet)" variant="light">Voir le détail</x-ui.button>
                            @else
                                <x-ui.button :href="route('trajets.show', $trajet)">Choisir ce trajet</x-ui.button>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-10 text-center shadow-card">
                    <x-ui.icon class="text-outline">search_off</x-ui.icon>
                    <p class="mt-2 font-body-lg text-body-lg text-on-surface-variant">
                        Aucun trajet ne correspond à votre recherche. Essayez une autre date ou une autre ville.
                    </p>
                </div>
            @endforelse
        </div>

        <div>{{ $trajets->links() }}</div>

        {{-- Informations --}}
        <section class="grid grid-cols-1 gap-gutter md:grid-cols-3">
            @foreach ([
                ['payments', 'Paiement mobile sécurisé', 'Réglez avec Wave ou Orange Money et retrouvez votre billet avec QR code dans votre espace.'],
                ['event_busy', 'Annulation encadrée', 'Une réservation confirmée est annulable jusqu\'à ' . \App\Models\Reservation::DELAI_ANNULATION_HEURES . ' heures avant le départ.'],
                ['timer', 'Sièges retenus ' . \App\Models\Reservation::DELAI_EXPIRATION_MINUTES . ' minutes', 'Après le choix de vos sièges, vous disposez de ce délai pour régler votre réservation.'],
            ] as [$icone, $titre, $texte])
                <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                    <x-ui.icon class="text-primary-container">{{ $icone }}</x-ui.icon>
                    <h3 class="mt-2 font-headline-sm text-headline-sm">{{ $titre }}</h3>
                    <p class="mt-1 font-body-md text-body-md text-on-surface-variant">{{ $texte }}</p>
                </div>
            @endforeach
        </section>
    </div>
</x-app-layout>