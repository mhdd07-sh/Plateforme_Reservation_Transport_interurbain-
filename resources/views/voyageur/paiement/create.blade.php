@php
    $trajet = $reservation->trajet;
    $user = auth()->user();
    $numeros = $reservation->sieges->pluck('numero')->sort()->implode(', ');
    $moyens = [
        'wave' => ['Wave', 'Paiement instantané via l\'application Wave.', 'Recommandé'],
        'orange_money' => ['Orange Money', 'Règlement sécurisé avec validation par code secret.', null],
        'carte' => ['Carte bancaire', 'Paiement par carte (simulation).', null],
    ];
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('voyageur.reservations.show', $reservation) }}" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container hover:underline">
                <x-ui.icon>arrow_back</x-ui.icon> Retour à ma réservation
            </a>
            <span class="inline-flex items-center gap-1 font-label-md text-label-md uppercase text-on-surface-variant">
                <x-ui.icon>lock</x-ui.icon> Session sécurisée · Étape 3/4
            </span>
        </div>

        <x-voyageur.etapes :actuelle="3" />

        <form method="POST" action="{{ route('voyageur.paiement.store', $reservation) }}"
              x-data="{
                  moyen: @js(old('moyen', 'wave')),
                  fin: {{ $reservation->expire_at->timestamp }},
                  reste: 0,
                  maj() { this.reste = Math.max(0, this.fin - Math.floor(Date.now() / 1000)); },
                  init() { this.maj(); setInterval(() => this.maj(), 1000); }
              }"
              class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
            @csrf

            <section class="space-y-6 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
                <div>
                    <p class="font-label-md text-label-md uppercase tracking-wider text-primary-container">Règlement mobile sécurisé</p>
                    <h1 class="mt-1 font-headline-lg-mobile text-headline-lg-mobile sm:font-headline-lg sm:text-headline-lg">Choisissez votre mode de paiement</h1>
                </div>

                <div class="space-y-3">
                    @foreach ($moyens as $cle => [$nom, $texte, $etiquette])
                        <label class="relative block cursor-pointer rounded-xl p-4 transition"
                               x-bind:class="moyen === '{{ $cle }}' ? 'border-2 border-primary-container bg-primary-fixed/20' : 'border border-outline-variant/60 hover:border-primary-container'">
                            <input type="radio" name="moyen" value="{{ $cle }}" x-model="moyen" class="sr-only">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-outline-variant"
                                          x-bind:class="moyen === '{{ $cle }}' ? '!border-primary-container' : ''">
                                        <span class="h-2.5 w-2.5 rounded-full bg-primary-container" x-show="moyen === '{{ $cle }}'"></span>
                                    </span>
                                    <span class="font-headline-sm text-headline-sm">{{ $nom }}</span>
                                </div>
                                @if ($etiquette)
                                    <x-ui.badge tone="success">{{ $etiquette }}</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-1 pl-8 font-body-md text-body-md text-on-surface-variant">{{ $texte }}</p>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('moyen')" />

                <div class="rounded-xl bg-surface-container-low p-5">
                    <div x-show="moyen !== 'carte'">
                        <x-input-label for="numero_mobile" value="Numéro de téléphone du compte *" />
                        <div class="mt-1 flex">
                            <span class="inline-flex min-h-[48px] items-center rounded-l-lg border border-r-0 border-[#D1D5DB] bg-surface-container px-3 font-label-lg text-label-lg text-on-surface-variant">+221</span>
                            <x-text-input id="numero_mobile" name="numero" type="tel" class="rounded-l-none"
                                :value="old('numero', \App\Support\Telephone::formater($user->telephone))" placeholder="77 000 00 00"
                                x-bind:disabled="moyen === 'carte'" />
                        </div>
                        <div class="mt-3 flex items-start gap-3 rounded-lg bg-surface-container-lowest p-3">
                            <x-ui.icon class="text-primary-container">notifications_active</x-ui.icon>
                            <p class="font-body-md text-body-md text-on-surface-variant">
                                Après avoir cliqué sur Payer, un code de confirmation vous est demandé pour valider
                                <strong>{{ number_format($reservation->montant_total, 0, ',', ' ') }} FCFA</strong>.
                            </p>
                        </div>
                    </div>

                    <div x-show="moyen === 'carte'" x-cloak>
                        <x-input-label for="numero_carte" value="Numéro de carte *" />
                        <x-text-input id="numero_carte" name="numero" type="text" inputmode="numeric" class="mt-1"
                            placeholder="4111 1111 1111 1111" x-bind:disabled="moyen !== 'carte'" />
                        <p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">Simulation : seuls les 4 derniers chiffres sont conservés.</p>
                    </div>
                    <x-input-error :messages="$errors->get('numero')" class="mt-2" />
                </div>

                <label class="flex items-start gap-3">
                    <input type="checkbox" name="accepte_cgv" value="1" @checked(old('accepte_cgv'))
                           class="mt-0.5 rounded border-[#D1D5DB] text-primary-container focus:ring-primary-container/30">
                    <span class="font-body-md text-body-md text-on-surface-variant">
                        J'accepte les <span class="font-label-lg text-label-lg text-primary-container">conditions générales de vente</span>
                        et la politique d'annulation (jusqu'à {{ \App\Models\Reservation::DELAI_ANNULATION_HEURES }} h avant le départ).
                    </span>
                </label>
                <x-input-error :messages="$errors->get('accepte_cgv')" />

                <x-primary-button class="w-full">
                    Payer {{ number_format($reservation->montant_total, 0, ',', ' ') }} FCFA
                    <x-ui.icon>arrow_forward</x-ui.icon>
                </x-primary-button>
            </section>

            {{-- Récapitulatif --}}
            <aside class="h-fit space-y-4 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card lg:sticky lg:top-24">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline-md text-headline-md">Récapitulatif</h2>
                    <x-ui.badge>{{ $reservation->nombre_places }} passager(s)</x-ui.badge>
                </div>

                <div class="rounded-xl bg-surface-container-low p-4">
                    <div class="font-headline-sm text-headline-sm">{{ $trajet->ville_depart }} → {{ $trajet->ville_arrivee }}</div>
                    <div class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                        {{ $trajet->date_depart->translatedFormat('l d F Y') }} · Départ {{ substr($trajet->heure_depart, 0, 5) }}
                    </div>
                    <div class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ $trajet->transporteur->nom_compagnie ?? 'Transporteur' }} · {{ ucfirst($trajet->vehicule->type_vehicule) }}
                    </div>
                </div>

                <div class="rounded-xl bg-primary-container p-4 text-on-primary">
                    <div class="font-label-sm text-label-sm uppercase text-on-primary-container">Place(s) réservée(s)</div>
                    <div class="font-headline-md text-headline-md">Siège(s) n° {{ $numeros }}</div>
                </div>

                <dl class="space-y-2 font-body-md text-body-md">
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Tarif ({{ $reservation->nombre_places }} × {{ number_format($trajet->prix_unitaire, 0, ',', ' ') }})</dt><dd>{{ number_format($reservation->montant_total, 0, ',', ' ') }} FCFA</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Frais de service</dt><dd>0 FCFA</dd></div>
                </dl>

                <div class="flex items-center justify-between rounded-xl bg-surface-container-low p-4">
                    <span class="font-label-sm text-label-sm uppercase text-on-surface-variant">Total à payer</span>
                    <span class="font-headline-md text-headline-md text-primary-container">{{ number_format($reservation->montant_total, 0, ',', ' ') }} FCFA</span>
                </div>

                <div class="rounded-xl bg-secondary-fixed p-4 text-on-secondary-fixed">
                    <div class="font-label-sm text-label-sm uppercase">Sièges retenus encore</div>
                    <div class="font-headline-md text-headline-md" x-text="Math.floor(reste / 60) + ':' + String(reste % 60).padStart(2, '0')"></div>
                </div>
            </aside>
        </form>
    </div>
</x-app-layout>