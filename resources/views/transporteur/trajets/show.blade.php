@php
    $v = $trajet->vehicule;
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <a href="{{ route('transporteur.trajets.index') }}" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container hover:underline">
            <x-ui.icon>arrow_back</x-ui.icon> Mes trajets
        </a>

        <x-page-header :titre="$trajet->ville_depart . ' → ' . $trajet->ville_arrivee"
            :soustitre="$trajet->date_depart->translatedFormat('l d F Y') . ' à ' . substr($trajet->heure_depart, 0, 5)"
            surtitre="Détail du trajet">
            <x-ui.badge :tone="$trajet->toneStatut()">{{ $trajet->libelleStatut() }}</x-ui.badge>

            @can('update', $trajet)
                <x-ui.button :href="route('transporteur.trajets.edit', $trajet)" variant="light"><x-ui.icon>edit</x-ui.icon> Modifier</x-ui.button>
            @endcan

            @can('publier', $trajet)
                <form method="POST" action="{{ route('transporteur.trajets.publier', $trajet) }}">
                    @csrf
                    <x-ui.button type="submit"><x-ui.icon>publish</x-ui.icon> Publier</x-ui.button>
                </form>
            @endcan
        </x-page-header>
        
        @if ($trajet->statut === 'annule')
            <div class="rounded-lg bg-error-container px-4 py-3 font-body-md text-body-md text-on-error-container">
                Trajet annulé{{ $trajet->motif_annulation ? ' : ' . $trajet->motif_annulation : '' }}.
            </div>
        @endif

        @can('annuler', $trajet)
            @php $reservationsActives = $reservations->whereIn('statut', ['en_attente', 'confirmee'])->count(); @endphp

            <section id="annulation" class="rounded-xl border border-error/30 bg-error-container/40 p-6"
                     x-data="{ ouvert: {{ $errors->any() ? 'true' : 'false' }} }">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="inline-flex items-center gap-2 font-headline-sm text-headline-sm text-error">
                            <x-ui.icon>warning</x-ui.icon> Annuler ce trajet
                        </h2>
                        <p class="font-body-md text-body-md text-on-error-container">
                            Les voyageurs concernés sont remboursés automatiquement et le trajet n'est plus proposé.
                        </p>
                    </div>
                    <x-ui.button type="button" variant="danger" x-on:click="ouvert = ! ouvert">Annuler le trajet</x-ui.button>
                </div>

                <form method="POST" action="{{ route('transporteur.trajets.annuler', $trajet) }}" x-show="ouvert" x-cloak class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="motif" value="Motif (communiqué aux voyageurs)" />
                        <x-text-input id="motif" name="motif" type="text" maxlength="255" class="mt-1"
                            :value="old('motif')" placeholder="Ex : panne du véhicule" />
                        <x-input-error :messages="$errors->get('motif')" class="mt-2" />
                    </div>

                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="confirmation" value="1"
                               class="mt-0.5 rounded border-[#D1D5DB] text-primary-container focus:ring-primary-container/30">
                        <span class="font-body-md text-body-md text-on-error-container">
                            Je confirme l'annulation.
                            {{ $reservationsActives > 0 ? $reservationsActives . ' réservation(s) seront annulées et remboursées.' : 'Aucune réservation à rembourser.' }}
                        </span>
                    </label>
                    <x-input-error :messages="$errors->get('confirmation')" />

                    <x-ui.button type="submit" variant="danger">Confirmer l'annulation</x-ui.button>
                </form>
            </section>
        @endcan

        <section class="grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                <div class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Véhicule</div>
                <div class="mt-1 font-headline-sm text-headline-sm">{{ $v->immatriculation }}</div>
                <div class="font-body-sm text-body-sm text-on-surface-variant">{{ \App\Models\Vehicule::libelleType($v->type_vehicule) }} · {{ $v->marque }}</div>
            </div>
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                <div class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Tarif par place</div>
                <div class="mt-1 font-headline-sm text-headline-sm text-primary-container">{{ number_format($trajet->prix_unitaire, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                <div class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Remplissage</div>
                <div class="mt-2">@include('transporteur._remplissage', ['occupes' => $occupes, 'capacite' => $v->capacite])</div>
            </div>
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                <div class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Revenus confirmés</div>
                <div class="mt-1 font-headline-sm text-headline-sm">{{ number_format($revenus, 0, ',', ' ') }} FCFA</div>
            </div>
        </section>

        <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
            <h2 class="font-headline-md text-headline-md">Passagers et réservations</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="border-b border-outline-variant/40 font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
                        <tr>
                            <th class="px-4 py-3">Passager</th>
                            <th class="px-4 py-3">Référence</th>
                            <th class="px-4 py-3">Siège(s)</th>
                            <th class="px-4 py-3">Montant</th>
                            <th class="px-4 py-3">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30 font-body-md text-body-md">
                        @forelse ($reservations as $r)
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="font-label-lg text-label-lg">{{ $r->user->name }}</div>
                                    @if ($r->user->telephone)
                                        <div class="font-body-sm text-body-sm text-on-surface-variant">+221 {{ \App\Support\Telephone::formater($r->user->telephone) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 font-label-lg text-label-lg text-primary-container">{{ $r->reference }}</td>
                                <td class="px-4 py-4">{{ $r->sieges->pluck('numero')->sort()->implode(', ') ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4">{{ number_format($r->montant_total, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-4"><x-ui.badge :tone="$r->toneStatut()">{{ $r->libelleStatut() }}</x-ui.badge></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-on-surface-variant">Aucune réservation sur ce trajet pour le moment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>