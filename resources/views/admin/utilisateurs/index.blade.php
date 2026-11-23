@php
    $selectCss = 'min-h-[48px] rounded-lg border border-[#D1D5DB] bg-white px-4 font-body-md text-body-md focus:border-primary-container focus:ring-2 focus:ring-primary-container/15';
    $tonesRole = ['voyageur' => 'info', 'transporteur' => 'warning', 'admin' => 'success'];
    $libellesRole = ['voyageur' => 'Voyageur', 'transporteur' => 'Transporteur', 'admin' => 'Super Admin'];
    $moi = auth()->id();
@endphp

<x-app-layout>
    <div class="mx-auto max-w-[1200px] space-y-6 px-4 py-8 sm:px-8">
        <x-page-header titre="Utilisateurs" surtitre="Administration"
            soustitre="Comptes voyageurs, transporteurs et administrateurs de la plateforme.">
            <x-ui.badge>{{ $total }} compte(s)</x-ui.badge>
            <x-ui.button :href="route('admin.transporteurs.create')"><x-ui.icon>person_add</x-ui.icon> Nouveau transporteur</x-ui.button>
        </x-page-header>

        <section class="grid grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Tous les rôles" :valeur="$total" icone="groups" tone="neutral" />
            <x-stat-card label="Voyageurs" :valeur="$parRole['voyageur'] ?? 0" icone="airline_seat_recline_normal" tone="info" />
            <x-stat-card label="Transporteurs" :valeur="$parRole['transporteur'] ?? 0" icone="local_shipping" tone="warning" />
            <x-stat-card label="Administrateurs" :valeur="$parRole['admin'] ?? 0" icone="admin_panel_settings" />
        </section>

        {{-- Filtres --}}
        <form method="GET" action="{{ route('admin.utilisateurs.index') }}"
              class="flex flex-wrap items-center gap-3 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-4 shadow-card">
            <div class="min-w-[220px] flex-1">
                <x-text-input name="q" type="search" :value="request('q')" placeholder="Nom, e-mail ou téléphone" />
            </div>
            <select name="role" class="{{ $selectCss }}" onchange="this.form.submit()">
                <option value="">Tous les rôles ({{ $total }})</option>
                @foreach (['voyageur' => 'Voyageurs', 'transporteur' => 'Transporteurs', 'admin' => 'Administrateurs'] as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(request('role') === $valeur)>{{ $libelle }} ({{ $parRole[$valeur] ?? 0 }})</option>
                @endforeach
            </select>
            <select name="statut" class="{{ $selectCss }}" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <option value="actif" @selected(request('statut') === 'actif')>Actifs ({{ $parStatut[1] ?? 0 }})</option>
                <option value="desactive" @selected(request('statut') === 'desactive')>Désactivés ({{ $parStatut[0] ?? 0 }})</option>
            </select>
            <select name="tri" class="{{ $selectCss }}" onchange="this.form.submit()">
                <option value="recents" @selected(request('tri', 'recents') === 'recents')>Plus récents d'abord</option>
                <option value="anciens" @selected(request('tri') === 'anciens')>Plus anciens d'abord</option>
                <option value="alpha" @selected(request('tri') === 'alpha')>Nom alphabétique (A-Z)</option>
                <option value="activite" @selected(request('tri') === 'activite')>Nombre de réservations</option>
            </select>
            <x-ui.button type="submit" variant="light"><x-ui.icon>search</x-ui.icon></x-ui.button>
            <a href="{{ route('admin.utilisateurs.index') }}" title="Réinitialiser" class="inline-flex h-12 w-12 items-center justify-center rounded-lg border border-outline-variant/40 text-on-surface-variant hover:bg-surface-container-low"><x-ui.icon>restart_alt</x-ui.icon></a>
        </form>

        <div class="grid gap-gutter {{ $selection ? 'lg:grid-cols-[minmax(0,1fr)_380px]' : '' }}">
            {{-- Liste --}}
            <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-4 shadow-card">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left">
                        <thead class="border-b border-outline-variant/40 font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
                            <tr>
                                <th class="px-4 py-3">Utilisateur</th>
                                <th class="px-4 py-3">Téléphone</th>
                                <th class="px-4 py-3">Rôle</th>
                                <th class="px-4 py-3">Activité</th>
                                <th class="px-4 py-3">Inscrit le</th>
                                <th class="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/30 font-body-md text-body-md">
                            @forelse ($utilisateurs as $u)
                                @php $estSelection = $selection && $selection->id === $u->id; @endphp
                                <tr class="{{ $estSelection ? 'bg-primary-fixed/30' : '' }} hover:bg-surface-container-low/60">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.utilisateurs.index', array_merge(request()->query(), ['utilisateur' => $u->id])) }}" class="flex items-center gap-3">
                                            <x-avatar :nom="$u->name" :tone="$u->actif ? ($tonesRole[$u->role] ?? 'success') : 'neutral'" />
                                            <div>
                                                <div class="flex items-center gap-2 font-label-lg text-label-lg">
                                                    {{ $u->name }}
                                                    @if ($u->id === $moi)<x-ui.badge>Vous</x-ui.badge>@endif
                                                </div>
                                                <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $u->email }}</div>
                                            </div>
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $u->telephone ? '+221 ' . \App\Support\Telephone::formater($u->telephone) : '—' }}</td>
                                    <td class="px-4 py-3"><x-ui.badge :tone="$tonesRole[$u->role] ?? 'neutral'">{{ $libellesRole[$u->role] ?? $u->role }}</x-ui.badge></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-label-lg text-label-lg">
                                        @if ($u->role === 'voyageur')
                                            {{ $u->reservations_count }} réservation(s)
                                        @elseif ($u->role === 'transporteur' && $u->transporteur)
                                            {{ $trajetsGeres[$u->transporteur->id] ?? 0 }} trajet(s) géré(s)
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $u->created_at->translatedFormat('d M Y') }}</td>
                                    <td class="px-4 py-3"><x-ui.badge :tone="$u->actif ? 'success' : 'neutral'">{{ $u->actif ? 'Actif' : 'Désactivé' }}</x-ui.badge></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-12 text-center text-on-surface-variant">Aucun utilisateur ne correspond à ces critères.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <span class="font-body-sm text-body-sm text-on-surface-variant">Affichage {{ $utilisateurs->firstItem() ?? 0 }} - {{ $utilisateurs->lastItem() ?? 0 }} sur {{ $utilisateurs->total() }} utilisateurs</span>
                    {{ $utilisateurs->links() }}
                </div>
            </section>

            {{-- Fiche utilisateur --}}
            @if ($selection)
                <aside class="h-fit rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <h2 class="inline-flex items-center gap-2 font-headline-sm text-headline-sm"><x-ui.icon class="text-primary-container">badge</x-ui.icon> Fiche utilisateur</h2>
                        <a href="{{ route('admin.utilisateurs.index', request()->except('utilisateur')) }}" title="Fermer" class="inline-flex h-9 w-9 items-center justify-center rounded-lg hover:bg-surface-container"><x-ui.icon>close</x-ui.icon></a>
                    </div>

                    <div class="mt-4 rounded-xl bg-surface-container-low p-5 text-center">
                        <x-avatar :nom="$selection->name" tone="dark" class="mx-auto !h-16 !w-16 !text-headline-sm" />
                        <div class="mt-2 font-headline-sm text-headline-sm">{{ $selection->name }}</div>
                        <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $selection->email }}</div>
                        <div class="mt-2 flex flex-wrap justify-center gap-2">
                            <x-ui.badge :tone="$selection->actif ? 'success' : 'neutral'">{{ $selection->actif ? 'Actif' : 'Désactivé' }}</x-ui.badge>
                            <x-ui.badge :tone="$tonesRole[$selection->role] ?? 'neutral'">{{ $libellesRole[$selection->role] ?? $selection->role }}</x-ui.badge>
                        </div>
                        @if ($selection->telephone)
                            <div class="mt-3 inline-flex items-center gap-1 rounded-lg bg-surface-container-lowest px-3 py-1.5 font-label-md text-label-md">
                                <x-ui.icon>smartphone</x-ui.icon> +221 {{ \App\Support\Telephone::formater($selection->telephone) }}
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-center">
                        @if ($selection->role === 'transporteur')
                            <div class="rounded-xl bg-surface-container-low p-3"><div class="font-headline-sm text-headline-sm">{{ $detail['trajets'] }}</div><div class="font-label-sm text-label-sm text-on-surface-variant">Trajets gérés</div></div>
                            <div class="rounded-xl bg-surface-container-low p-3"><div class="font-headline-sm text-headline-sm">{{ ucfirst($selection->transporteur?->statut ?? '—') }}</div><div class="font-label-sm text-label-sm text-on-surface-variant">Profil</div></div>
                        @else
                            <div class="rounded-xl bg-surface-container-low p-3"><div class="font-headline-sm text-headline-sm">{{ $detail['voyages'] }}</div><div class="font-label-sm text-label-sm text-on-surface-variant">Voyages</div></div>
                            <div class="rounded-xl bg-surface-container-low p-3"><div class="font-headline-sm text-headline-sm">{{ number_format($detail['paye'], 0, ',', ' ') }}</div><div class="font-label-sm text-label-sm text-on-surface-variant">FCFA payés</div></div>
                        @endif
                    </div>

                    @if ($detail['dernieres']->isNotEmpty())
                        <h3 class="mt-5 font-label-lg text-label-lg">Dernières réservations</h3>
                        <ul class="mt-2 space-y-2">
                            @foreach ($detail['dernieres'] as $r)
                                <li class="rounded-xl bg-surface-container-low p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-label-lg text-label-lg">{{ $r->trajet->ville_depart }} → {{ $r->trajet->ville_arrivee }}</span>
                                        <x-ui.badge :tone="$r->toneStatut()">{{ $r->libelleStatut() }}</x-ui.badge>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between font-body-sm text-body-sm text-on-surface-variant">
                                        <span>{{ $r->nombre_places }} place(s) · {{ $r->created_at->translatedFormat('d M Y') }}</span>
                                        <span class="font-label-md text-label-md text-on-surface">{{ number_format($r->montant_total, 0, ',', ' ') }} FCFA</span>
                                    </div>
                                    <div class="font-label-sm text-label-sm text-on-surface-variant">Billet #{{ $r->reference }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- Désactivation --}}
                    <div class="mt-5 rounded-xl bg-error-container/50 p-4">
                        @if ($selection->id === $moi)
                            <p class="font-body-md text-body-md text-on-error-container">Vous ne pouvez pas désactiver votre propre compte.</p>
                        @elseif ($selection->actif)
                            <div class="inline-flex items-center gap-1 font-label-lg text-label-lg text-error"><x-ui.icon>warning</x-ui.icon> Désactiver ce compte</div>
                            <p class="mt-1 font-body-md text-body-md text-on-error-container">
                                L'utilisateur sera déconnecté et ne pourra plus se connecter. Les réservations déjà payées restent valides.
                                @if ($selection->role === 'transporteur') Son profil transporteur sera suspendu. @endif
                            </p>
                            <p class="mt-2 rounded-lg bg-surface-container-lowest p-2 font-body-sm text-body-sm text-on-surface-variant">
                                Conformité : un compte avec un historique de réservations n'est jamais supprimé, seulement désactivé.
                            </p>
                            <form method="POST" action="{{ route('admin.utilisateurs.desactiver', $selection) }}" class="mt-3"
                                  onsubmit="return confirm('Désactiver ce compte ?')">
                                @csrf
                                <x-ui.button type="submit" variant="danger" class="w-full"><x-ui.icon>block</x-ui.icon> Désactiver le compte utilisateur</x-ui.button>
                            </form>
                        @else
                            <p class="font-body-md text-body-md text-on-error-container">Ce compte est désactivé : l'utilisateur ne peut plus se connecter.</p>
                            <form method="POST" action="{{ route('admin.utilisateurs.reactiver', $selection) }}" class="mt-3">
                                @csrf
                                <x-ui.button type="submit" class="w-full"><x-ui.icon>play_arrow</x-ui.icon> Réactiver le compte</x-ui.button>
                            </form>
                        @endif
                    </div>
                </aside>
            @endif
        </div>
    </div>
</x-app-layout>