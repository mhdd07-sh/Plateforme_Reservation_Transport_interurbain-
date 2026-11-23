@php
    $pourcentage = $stats['total'] > 0 ? (int) round($stats['actifs'] / $stats['total'] * 100) : 0;
    $filtreActif = request()->filled('type') || request()->filled('statut');
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <x-page-header titre="Mes véhicules" surtitre="Flotte"
            soustitre="Gérez votre flotte et sa disponibilité pour vos prochains départs.">
            <x-ui.button :href="route('transporteur.vehicules.create')"><x-ui.icon>add</x-ui.icon> Ajouter un véhicule</x-ui.button>
        </x-page-header>

        {{-- Chiffres clés --}}
        <section class="grid grid-cols-1 gap-gutter md:grid-cols-3">
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                <div class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Total véhicules</div>
                <div class="mt-1 font-headline-lg text-headline-lg">{{ $stats['total'] }}</div>
                <div class="font-body-sm text-body-sm text-on-surface-variant">unités enregistrées</div>
            </div>
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                <div class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Véhicules actifs</div>
                <div class="mt-1 font-headline-lg text-headline-lg">{{ $stats['actifs'] }}</div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-container-high">
                    <div class="h-full rounded-full bg-primary-container" style="width: {{ $pourcentage }}%"></div>
                </div>
                <div class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ $pourcentage }}% opérationnels</div>
            </div>
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                <div class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">Capacité totale</div>
                <div class="mt-1 font-headline-lg text-headline-lg">{{ $stats['capacite'] }}</div>
                <div class="font-body-sm text-body-sm text-on-surface-variant">places au total</div>
            </div>
        </section>

        {{-- Filtres --}}
        <form method="GET" action="{{ route('transporteur.vehicules.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-52">
                <x-input-label for="type" value="Type" />
                <select id="type" name="type" onchange="this.form.submit()"
                    class="mt-1 block min-h-[48px] w-full rounded-lg border border-[#D1D5DB] bg-white px-4 font-body-md text-body-md focus:border-primary-container focus:ring-2 focus:ring-primary-container/15">
                    <option value="">Tous les types</option>
                    @foreach (\App\Models\Vehicule::TYPES as $type)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ \App\Models\Vehicule::libelleType($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-52">
                <x-input-label for="statut" value="Statut" />
                <select id="statut" name="statut" onchange="this.form.submit()"
                    class="mt-1 block min-h-[48px] w-full rounded-lg border border-[#D1D5DB] bg-white px-4 font-body-md text-body-md focus:border-primary-container focus:ring-2 focus:ring-primary-container/15">
                    <option value="">Tous les statuts</option>
                    <option value="actif" @selected(request('statut') === 'actif')>Actif</option>
                    <option value="en_maintenance" @selected(request('statut') === 'en_maintenance')>En maintenance</option>
                </select>
            </div>
            @if ($filtreActif)
                <a href="{{ route('transporteur.vehicules.index') }}" class="pb-3 font-label-lg text-label-lg text-primary-container hover:underline">Réinitialiser les filtres</a>
            @endif
        </form>

        {{-- Flotte --}}
        @if ($vehicules->isEmpty())
            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-10 text-center shadow-card">
                <x-ui.icon class="text-outline">search_off</x-ui.icon>
                <h2 class="mt-2 font-headline-sm text-headline-sm">Aucun véhicule trouvé</h2>
                <p class="mx-auto mt-1 max-w-md font-body-md text-body-md text-on-surface-variant">
                    {{ $filtreActif ? 'Essayez de modifier vos filtres pour afficher les véhicules de votre flotte.' : 'Ajoutez votre premier véhicule pour pouvoir créer des trajets.' }}
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-3">
                @foreach ($vehicules as $vehicule)
                    <article class="flex flex-col rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="font-headline-sm text-headline-sm">{{ $vehicule->immatriculation }}</h2>
                                <p class="font-body-md text-body-md text-on-surface-variant">
                                    {{ \App\Models\Vehicule::libelleType($vehicule->type_vehicule) }} · {{ $vehicule->capacite }} pl. ({{ $vehicule->marque }})
                                </p>
                            </div>
                            @if ($vehicule->estActif())
                                <x-ui.badge tone="success">Actif</x-ui.badge>
                            @else
                                <x-ui.badge tone="warning">En maintenance</x-ui.badge>
                            @endif
                        </div>

                        <div class="mt-4 flex-1 rounded-lg bg-surface-container-low p-3">
                            @if ($vehicule->estActif())
                                <div class="font-label-sm text-label-sm uppercase text-on-surface-variant">Prochain départ</div>
                                @if ($vehicule->prochainTrajet)
                                    <div class="font-label-lg text-label-lg">
                                        {{ $vehicule->prochainTrajet->ville_depart }} → {{ $vehicule->prochainTrajet->ville_arrivee }}
                                    </div>
                                    <div class="font-body-sm text-body-sm text-on-surface-variant">
                                        {{ $vehicule->prochainTrajet->date_depart->translatedFormat('D d M') }}, {{ substr($vehicule->prochainTrajet->heure_depart, 0, 5) }}
                                    </div>
                                @else
                                    <div class="font-body-md text-body-md text-on-surface-variant">Aucun départ programmé</div>
                                @endif
                            @else
                                <div class="font-label-sm text-label-sm uppercase text-on-surface-variant">Note de maintenance</div>
                                <div class="font-body-md text-body-md">{{ $vehicule->note_maintenance ?: 'Aucune précision.' }}</div>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <x-ui.button :href="route('transporteur.vehicules.edit', $vehicule)" variant="light"><x-ui.icon>edit</x-ui.icon> Modifier</x-ui.button>
                            <form method="POST" action="{{ route('transporteur.vehicules.destroy', $vehicule) }}"
                                  onsubmit="return confirm('Supprimer ce véhicule ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Supprimer" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-error hover:bg-error-container/50">
                                    <x-ui.icon>delete</x-ui.icon>
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>