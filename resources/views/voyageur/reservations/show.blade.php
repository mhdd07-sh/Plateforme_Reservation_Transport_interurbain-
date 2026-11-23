@php
    $t = $reservation->trajet;
    $user = $reservation->user;
    $numeros = $reservation->sieges->pluck('numero')->sort()->implode(', ');
    $moyen = \App\Models\Paiement::libelleMoyen($reservation->operateur_paiement);
    $messageWa = config('app.name') . " : mon billet {$t->ville_depart} → {$t->ville_arrivee}, le "
        . $t->date_depart->format('d/m/Y') . ' à ' . substr($t->heure_depart, 0, 5)
        . ". Référence : {$reservation->reference}";
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <a href="{{ route('voyageur.reservations.index') }}" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container hover:underline print:hidden">
            <x-ui.icon>arrow_back</x-ui.icon> Mes réservations
        </a>

        {{-- ================= EN ATTENTE DE PAIEMENT ================= --}}
        @if ($reservation->statut === 'en_attente')
            <x-voyageur.etapes :actuelle="3" />

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
                <section class="space-y-4 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                    <div class="flex items-center justify-between gap-3">
                        <h1 class="font-headline-md text-headline-md">{{ $t->ville_depart }} → {{ $t->ville_arrivee }}</h1>
                        <x-ui.badge tone="warning">En attente de paiement</x-ui.badge>
                    </div>
                    <dl class="grid grid-cols-1 gap-4 font-body-md text-body-md sm:grid-cols-2">
                        <div><dt class="text-on-surface-variant">Départ</dt><dd class="font-label-lg text-label-lg">{{ $t->date_depart->translatedFormat('l d F Y') }} à {{ substr($t->heure_depart, 0, 5) }}</dd></div>
                        <div><dt class="text-on-surface-variant">Transporteur</dt><dd class="font-label-lg text-label-lg">{{ $t->transporteur->nom_compagnie ?? 'Transporteur' }}</dd></div>
                        <div><dt class="text-on-surface-variant">Siège(s)</dt><dd class="font-label-lg text-label-lg">{{ $numeros }}</dd></div>
                        <div><dt class="text-on-surface-variant">Montant à payer</dt><dd class="font-headline-sm text-headline-sm text-primary-container">{{ number_format($reservation->montant_total, 0, ',', ' ') }} FCFA</dd></div>
                    </dl>
                </section>

                <aside class="h-fit space-y-4 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card"
                       x-data="{
                           fin: {{ $reservation->expire_at->timestamp }},
                           reste: 0,
                           maj() { this.reste = Math.max(0, this.fin - Math.floor(Date.now() / 1000)); },
                           init() { this.maj(); setInterval(() => this.maj(), 1000); }
                       }">
                    <div>
                        <div class="font-body-md text-body-md text-on-surface-variant">Vos sièges sont retenus encore</div>
                        <div class="font-display-hero-mobile text-display-hero-mobile" x-bind:class="reste < 60 ? 'text-error' : ''"
                             x-text="Math.floor(reste / 60) + ':' + String(reste % 60).padStart(2, '0')"></div>
                        <p class="mt-1 font-body-sm text-body-sm text-error" x-show="reste === 0">Délai dépassé : rechargez la page, les sièges seront libérés.</p>
                    </div>

                    <x-ui.button :href="route('voyageur.paiement.create', $reservation)" class="w-full">
                        Payer {{ number_format($reservation->montant_total, 0, ',', ' ') }} FCFA <x-ui.icon>arrow_forward</x-ui.icon>
                    </x-ui.button>

                    <form method="POST" action="{{ route('voyageur.reservations.abandonner', $reservation) }}"
                          onsubmit="return confirm('Abandonner cette réservation et libérer les sièges ?')">
                        @csrf
                        <button type="submit" class="w-full text-center font-label-lg text-label-lg text-error hover:underline">Abandonner la réservation</button>
                    </form>
                </aside>
            </div>

        {{-- ================= CONFIRMÉE : E-BILLET ================= --}}
        @elseif ($reservation->statut === 'confirmee')
            <x-voyageur.etapes :actuelle="5" />

            <section class="flex items-center gap-4 rounded-xl bg-primary-fixed/40 p-5 print:hidden">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary-fixed text-on-primary-fixed-variant">
                    <x-ui.icon>check_circle</x-ui.icon>
                </div>
                <div>
                    <h1 class="font-headline-md text-headline-md">Réservation confirmée ! Votre billet électronique est prêt.</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">Présentez ce QR code à l'embarquement.</p>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-elevated">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-primary px-6 py-4 text-on-primary">
                    <div class="flex items-center gap-3">
                        <x-ui.icon>directions_bus</x-ui.icon>
                        <div>
                            <div class="font-headline-sm text-headline-sm">{{ config('app.name') }} · E-billet officiel</div>
                            <div class="font-label-sm text-label-sm uppercase text-on-primary-container">Titre de transport interurbain</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <div class="font-label-sm text-label-sm uppercase text-on-primary-container">Référence</div>
                            <div class="font-headline-sm text-headline-sm">{{ $reservation->reference }}</div>
                        </div>
                        <span class="rounded-full bg-secondary-container px-3 py-1 font-label-md text-label-md uppercase text-on-secondary-container">
                            Confirmé · Payé par {{ $moyen }}
                        </span>
                    </div>
                </div>

                <div class="grid lg:grid-cols-[minmax(0,1fr)_300px]">
                    <div class="space-y-6 p-6">
                        <div class="flex items-center justify-between gap-4 rounded-xl bg-surface-container-low p-5">
                            <div>
                                <div class="font-label-sm text-label-sm uppercase text-on-surface-variant">Départ</div>
                                <div class="font-headline-lg-mobile text-headline-lg-mobile">{{ $t->ville_depart }}</div>
                            </div>
                            <div class="hidden flex-1 items-center gap-2 text-outline sm:flex">
                                <span class="h-2 w-2 rounded-full bg-primary-container"></span>
                                <span class="h-px flex-1 bg-outline-variant"></span>
                                <x-ui.icon>arrow_forward</x-ui.icon>
                                <span class="h-px flex-1 bg-outline-variant"></span>
                                <span class="h-2 w-2 rounded-full bg-secondary-container"></span>
                            </div>
                            <div class="text-right">
                                <div class="font-label-sm text-label-sm uppercase text-on-surface-variant">Destination</div>
                                <div class="font-headline-lg-mobile text-headline-lg-mobile">{{ $t->ville_arrivee }}</div>
                            </div>
                        </div>

                        <dl class="grid grid-cols-2 gap-5 font-body-md text-body-md sm:grid-cols-3">
                            <div><dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">Date du voyage</dt><dd class="font-label-lg text-label-lg">{{ $t->date_depart->translatedFormat('l d F Y') }}</dd></div>
                            <div><dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">Départ</dt><dd class="font-label-lg text-label-lg">{{ substr($t->heure_depart, 0, 5) }}</dd></div>
                            <div><dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">Véhicule</dt><dd class="font-label-lg text-label-lg">{{ ucfirst($t->vehicule->type_vehicule) }} · {{ $t->vehicule->immatriculation }}</dd></div>
                            <div><dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">Transporteur</dt><dd class="font-label-lg text-label-lg">{{ $t->transporteur->nom_compagnie ?? 'Transporteur' }}</dd></div>
                            <div>
                                <dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">Passager</dt>
                                <dd class="font-label-lg text-label-lg">{{ $user->name }}</dd>
                                @if ($user->telephone)<dd class="font-body-sm text-body-sm text-on-surface-variant">+221 {{ \App\Support\Telephone::formater($user->telephone) }}</dd>@endif
                            </div>
                            <div><dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">Siège(s) attribué(s)</dt><dd class="font-label-lg text-label-lg text-primary-container">N° {{ $numeros }}</dd></div>
                            <div><dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">Montant réglé</dt><dd class="font-headline-sm text-headline-sm">{{ number_format($reservation->montant_total, 0, ',', ' ') }} FCFA</dd></div>
                        </dl>
                    </div>

                    <div class="flex flex-col items-center justify-center gap-3 border-t border-dashed border-outline-variant bg-surface-container-low p-6 lg:border-l lg:border-t-0">
                        <div class="font-label-md text-label-md uppercase tracking-wider text-primary-container">Pass d'embarquement</div>
                        @if ($qr)
                            <div class="rounded-xl bg-white p-3 shadow-card [&>svg]:h-44 [&>svg]:w-44">{!! $qr !!}</div>
                        @else
                            <div class="flex h-44 w-44 items-center justify-center rounded-xl bg-white p-3 text-center font-body-sm text-body-sm text-on-surface-variant shadow-card">
                                QR code indisponible
                            </div>
                        @endif
                        <div class="rounded-lg bg-white px-3 py-1 font-label-lg text-label-lg tracking-wider">{{ $reservation->reference }}</div>
                        <p class="text-center font-body-sm text-body-sm text-on-surface-variant">À présenter au chauffeur ou au chef de gare avant la montée.</p>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-3 print:hidden">
                <x-ui.button type="button" onclick="window.print()"><x-ui.icon>download</x-ui.icon> Imprimer / Enregistrer en PDF</x-ui.button>
                <x-ui.button :href="'https://wa.me/?text=' . rawurlencode($messageWa)" variant="light"><x-ui.icon>share</x-ui.icon> Envoyer sur WhatsApp</x-ui.button>
            </div>

            <section class="grid grid-cols-1 gap-gutter md:grid-cols-2 print:hidden">
                <div class="flex items-start gap-4 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                    <x-ui.icon class="text-primary-container">schedule</x-ui.icon>
                    <div>
                        <h2 class="font-headline-sm text-headline-sm">Consignes d'embarquement</h2>
                        <p class="font-body-md text-body-md text-on-surface-variant">Présentez-vous 30 minutes avant le départ avec une pièce d'identité valide et ce QR code sur votre téléphone.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                    <x-ui.icon class="text-primary-container">event_busy</x-ui.icon>
                    <div>
                        <h2 class="font-headline-sm text-headline-sm">Besoin d'annuler ?</h2>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            L'annulation est possible jusqu'à {{ \App\Models\Reservation::DELAI_ANNULATION_HEURES }} heures avant le départ.
                            @if (Route::has('voyageur.reservations.annuler') && $reservation->peutEtreAnnulee())
                                <form method="POST" action="{{ route('voyageur.reservations.annuler', $reservation) }}" class="mt-2"
                                      onsubmit="return confirm('Annuler cette réservation ?')">
                                    @csrf
                                    <button type="submit" class="font-label-lg text-label-lg text-error hover:underline">Annuler ma réservation</button>
                                </form>
                            @endif
                        </p>
                    </div>
                </div>
            </section>

        {{-- ================= ANNULÉE / EXPIRÉE ================= --}}
        @else
            <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-8 text-center shadow-card">
                <x-ui.badge :tone="$reservation->toneStatut()">{{ $reservation->libelleStatut() }}</x-ui.badge>
                <h1 class="mt-3 font-headline-md text-headline-md">{{ $t->ville_depart }} → {{ $t->ville_arrivee }}</h1>
                <p class="mt-2 font-body-md text-body-md text-on-surface-variant">
                    {{ $reservation->statut === 'expiree' ? 'Le délai de paiement est dépassé : les sièges ont été libérés.' : 'Cette réservation est annulée.' }}
                </p>
                <x-ui.button :href="route('trajets.show', $t)" class="mt-5">Réserver à nouveau</x-ui.button>
            </section>
        @endif
    </div>
</x-app-layout>