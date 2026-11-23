@php
    $vehicule = $trajet->vehicule;
    $libres = max(0, $vehicule->capacite - count($occupes));
    $user = auth()->user();
    $selectionnable = $user?->estVoyageur() && Route::has('voyageur.reservations.store') && $libres > 0;
    $numeros = $vehicule->sieges->pluck('numero', 'id');
    $compagnie = $trajet->transporteur->nom_compagnie ?? 'Transporteur';
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <a href="{{ route('trajets.index') }}" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container hover:underline">
            <x-ui.icon>arrow_back</x-ui.icon> Retour aux résultats de recherche
        </a>

        <x-voyageur.etapes :actuelle="2" />

        {{-- En-tête du trajet --}}
        <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-6">
                <div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge tone="success"><x-ui.icon>event_seat</x-ui.icon> {{ $libres }} place(s) restante(s)</x-ui.badge>
                        <x-ui.badge tone="warning"><x-ui.icon>verified</x-ui.icon> Transporteur validé</x-ui.badge>
                    </div>
                    <h1 class="mt-3 font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">
                        {{ $trajet->ville_depart }} → {{ $trajet->ville_arrivee }}
                    </h1>
                </div>

                <div class="flex items-center gap-3 rounded-xl bg-surface-container-low p-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-container font-headline-sm text-headline-sm text-on-primary">
                        {{ mb_strtoupper(mb_substr($compagnie, 0, 2)) }}
                    </span>
                    <div>
                        <div class="font-headline-sm text-headline-sm">{{ $compagnie }}</div>
                        <div class="font-body-sm text-body-sm text-on-surface-variant">
                            {{ ucfirst($vehicule->type_vehicule) }} · {{ $vehicule->marque }} · {{ $vehicule->capacite }} places
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 grid gap-4 rounded-xl bg-surface-container-low p-4 sm:grid-cols-3">
                <div class="flex items-center gap-3">
                    <x-ui.icon class="text-primary-container">calendar_month</x-ui.icon>
                    <div>
                        <div class="font-label-sm text-label-sm uppercase text-on-surface-variant">Date du trajet</div>
                        <div class="font-label-lg text-label-lg">{{ $trajet->date_depart->translatedFormat('l d F Y') }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <x-ui.icon class="text-primary-container">schedule</x-ui.icon>
                    <div>
                        <div class="font-label-sm text-label-sm uppercase text-on-surface-variant">Heure de départ</div>
                        <div class="font-label-lg text-label-lg">{{ substr($trajet->heure_depart, 0, 5) }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <x-ui.icon class="text-primary-container">payments</x-ui.icon>
                    <div>
                        <div class="font-label-sm text-label-sm uppercase text-on-surface-variant">Prix par place</div>
                        <div class="font-label-lg text-label-lg">{{ number_format($trajet->prix_unitaire, 0, ',', ' ') }} FCFA</div>
                    </div>
                </div>
            </div>
        </section>

        <form method="POST"
              action="{{ $selectionnable ? route('voyageur.reservations.store', $trajet) : '#' }}"
              x-data="{
                  selection: [],
                  prix: {{ $trajet->prix_unitaire }},
                  max: {{ \App\Services\ReservationService::MAX_SIEGES_PAR_RESERVATION }},
                  numeros: @js($numeros),
                  get libelleSieges() { return this.selection.map(id => this.numeros[id]).sort((a, b) => a - b).join(', '); },
                  get total() { return new Intl.NumberFormat('fr-FR').format(this.selection.length * this.prix) + ' FCFA'; }
              }"
              class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
            @csrf

            {{-- Plan du véhicule --}}
            <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                <p class="font-label-md text-label-md uppercase tracking-wider text-primary-container">Configuration du véhicule</p>
                <h2 class="mt-1 font-headline-md text-headline-md">Sélectionnez votre siège dans le véhicule</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Cliquez sur un siège libre pour le réserver.</p>

                <div class="mt-4 flex flex-wrap gap-4 rounded-xl bg-surface-container-low p-3 font-label-md text-label-md text-on-surface-variant">
                    <span class="inline-flex items-center gap-2"><span class="h-5 w-5 rounded-md border border-primary-container bg-white"></span> Libre</span>
                    <span class="inline-flex items-center gap-2"><span class="h-5 w-5 rounded-md bg-primary-container"></span> Votre sélection</span>
                    <span class="inline-flex items-center gap-2"><span class="h-5 w-5 rounded-md bg-surface-container-high"></span> Occupé</span>
                    <span class="inline-flex items-center gap-2"><span class="h-5 w-5 rounded-md bg-inverse-surface"></span> Cabine chauffeur</span>
                </div>

                <div class="mx-auto mt-6 max-w-sm rounded-3xl border-2 border-outline-variant bg-surface-container-low p-4">
                    <div class="mb-4 flex items-center justify-between font-label-sm text-label-sm uppercase text-on-surface-variant">
                        <span class="inline-flex items-center gap-1"><x-ui.icon>directions_bus</x-ui.icon> Avant du véhicule</span>
                        <span>Porte d'embarquement</span>
                    </div>

                    <div class="mb-3 flex h-14 w-24 flex-col items-center justify-center rounded-lg bg-inverse-surface text-inverse-on-surface">
                        <x-ui.icon>sports_motorsports</x-ui.icon>
                        <span class="font-label-sm text-label-sm">Chauffeur</span>
                    </div>

                    <div class="space-y-2">
                        @foreach ($vehicule->sieges->chunk(4) as $rangee)
                            <div class="flex justify-center gap-2">
                                @foreach ($rangee as $siege)
                                    @if ($loop->index === 2)
                                        <div class="w-6"></div>
                                    @endif

                                    @if (in_array($siege->id, $occupes))
                                        <span title="Déjà réservé" class="flex h-14 w-14 cursor-not-allowed flex-col items-center justify-center rounded-lg bg-surface-container-high text-outline">
                                            <x-ui.icon>lock</x-ui.icon>
                                            <span class="font-label-sm text-label-sm">{{ str_pad($siege->numero, 2, '0', STR_PAD_LEFT) }}</span>
                                        </span>
                                    @elseif ($selectionnable)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="sieges[]" value="{{ $siege->id }}" x-model="selection" class="peer sr-only"
                                                   x-bind:disabled="selection.length >= max && ! selection.includes('{{ $siege->id }}')">
                                            <span class="flex h-14 w-14 flex-col items-center justify-center rounded-lg border border-primary-container bg-white text-on-surface transition hover:bg-primary-fixed/40 peer-checked:bg-primary-container peer-checked:text-on-primary peer-disabled:cursor-not-allowed peer-disabled:opacity-40">
                                                <x-ui.icon>event_seat</x-ui.icon>
                                                <span class="font-label-sm text-label-sm">{{ str_pad($siege->numero, 2, '0', STR_PAD_LEFT) }}</span>
                                            </span>
                                        </label>
                                    @else
                                        <span class="flex h-14 w-14 flex-col items-center justify-center rounded-lg border border-primary-container bg-white text-on-surface">
                                            <x-ui.icon>event_seat</x-ui.icon>
                                            <span class="font-label-sm text-label-sm">{{ str_pad($siege->numero, 2, '0', STR_PAD_LEFT) }}</span>
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <p class="mt-4 text-center font-label-sm text-label-sm text-on-surface-variant">Arrière du véhicule</p>
                </div>

                @error('sieges')
                    <p class="mt-4 text-sm text-error">{{ $message }}</p>
                @enderror

                <div class="mt-6 flex items-start gap-3 rounded-xl bg-surface-container-low p-4">
                    <x-ui.icon class="text-primary-container">schedule</x-ui.icon>
                    <div>
                        <h3 class="font-headline-sm text-headline-sm">Ponctualité et embarquement</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            Présentez-vous en gare 30 minutes avant le départ, avec votre QR code et une pièce d'identité valide.
                        </p>
                    </div>
                </div>
            </section>

            {{-- Récapitulatif --}}
            <aside class="h-fit space-y-4 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card lg:sticky lg:top-24">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline-md text-headline-md">Récapitulatif</h2>
                    <x-ui.badge x-text="selection.length + (selection.length > 1 ? ' places' : ' place')">0 place</x-ui.badge>
                </div>

                <div class="rounded-xl bg-surface-container-low p-4">
                    <div class="font-label-lg text-label-lg">{{ $trajet->ville_depart }} → {{ $trajet->ville_arrivee }}</div>
                    <div class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                        {{ $trajet->date_depart->translatedFormat('D d M Y') }} · Départ {{ substr($trajet->heure_depart, 0, 5) }}
                    </div>
                    <div class="font-body-sm text-body-sm text-on-surface-variant">{{ ucfirst($vehicule->type_vehicule) }} · {{ $compagnie }}</div>
                </div>

                <div class="rounded-xl bg-primary-container p-4 text-on-primary">
                    <div class="font-label-sm text-label-sm uppercase text-on-primary-container">Siège(s) sélectionné(s)</div>
                    <div class="mt-1 font-headline-md text-headline-md" x-show="selection.length > 0" x-text="libelleSieges"></div>
                    <div class="mt-1 font-body-md text-body-md" x-show="selection.length === 0">Aucun siège sélectionné</div>
                </div>

                @if ($user)
                    <div class="flex items-center gap-3 rounded-xl bg-surface-container-low p-3">
                        <x-ui.icon class="text-primary-container">account_circle</x-ui.icon>
                        <div>
                            <div class="font-label-lg text-label-lg">{{ $user->name }}</div>
                            @if ($user->telephone)
                                <div class="font-body-sm text-body-sm text-on-surface-variant">+221 {{ \App\Support\Telephone::formater($user->telephone) }}</div>
                            @endif
                        </div>
                    </div>
                @endif

                <dl class="space-y-2 font-body-md text-body-md">
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Tarif par place</dt><dd>{{ number_format($trajet->prix_unitaire, 0, ',', ' ') }} FCFA</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Frais de réservation</dt><dd>0 FCFA</dd></div>
                </dl>

                <div class="rounded-xl bg-surface-container-low p-4">
                    <div class="font-label-sm text-label-sm uppercase text-on-surface-variant">Total à payer</div>
                    <div class="font-headline-lg text-headline-lg text-primary-container" x-text="total">0 FCFA</div>
                </div>

                @if ($selectionnable)
                    <x-primary-button class="w-full" x-bind:disabled="selection.length === 0">
                        Continuer vers le paiement <x-ui.icon>arrow_forward</x-ui.icon>
                    </x-primary-button>
                    <p class="text-center font-label-sm text-label-sm text-on-surface-variant">
                        Vos sièges sont retenus {{ \App\Models\Reservation::DELAI_EXPIRATION_MINUTES }} minutes.
                        Maximum {{ \App\Services\ReservationService::MAX_SIEGES_PAR_RESERVATION }} sièges.
                    </p>
                @elseif ($libres === 0)
                    <p class="rounded-lg bg-error-container px-4 py-3 text-center font-label-lg text-label-lg text-on-error-container">Ce trajet est complet.</p>
                @elseif (! $user)
                    <div class="space-y-2">
                        <p class="font-body-md text-body-md text-on-surface-variant">Connectez-vous pour choisir vos sièges et réserver.</p>
                        <x-ui.button :href="route('login')" class="w-full">Se connecter</x-ui.button>
                        <x-ui.button :href="route('register')" variant="outline" class="w-full">Créer un compte</x-ui.button>
                    </div>
                @elseif (! $user->estVoyageur())
                    <p class="rounded-lg bg-surface-container px-4 py-3 font-body-md text-body-md text-on-surface-variant">
                        Seuls les comptes voyageurs peuvent réserver.
                    </p>
                @endif

                <div class="flex items-center justify-center gap-2 font-label-sm text-label-sm text-on-surface-variant">
                    <x-ui.badge tone="info">Wave</x-ui.badge>
                    <x-ui.badge tone="warning">Orange Money</x-ui.badge>
                </div>
            </aside>
        </form>
    </div>
</x-app-layout>