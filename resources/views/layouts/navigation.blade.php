@php
    $user = auth()->user();

    $lien = fn (string $route, string $label, ?string $motif = null) => Route::has($route)
        ? ['url' => route($route), 'actif' => request()->routeIs($motif ?? $route), 'label' => $label]
        : null;

    if ($user?->estVoyageur()) {
        $espace = 'Espace voyageur';
        $role = 'Voyageur';
        $liens = [
            $lien('dashboard', 'Tableau de bord'),
            $lien('trajets.index', 'Rechercher un trajet', 'trajets.*'),
            $lien('voyageur.reservations.index', 'Mes réservations', 'voyageur.reservations.*'),
        ];
    } elseif ($user?->estTransporteur()) {
        $espace = 'Espace transporteur';
        $role = 'Transporteur';
        $liens = [
            $lien('transporteur.dashboard', 'Tableau de bord'),
            $lien('transporteur.vehicules.index', 'Mes véhicules', 'transporteur.vehicules.*'),
            $lien('transporteur.trajets.index', 'Mes trajets', 'transporteur.trajets.*'),
        ];
    } elseif ($user?->estAdmin()) {
        $espace = 'Admin';
        $role = 'Super Admin';
        $liens = [
            $lien('admin.dashboard', 'Tableau de bord'),
            $lien('admin.transporteurs.index', 'Transporteurs', 'admin.transporteurs.*'),
            $lien('admin.utilisateurs.index', 'Utilisateurs', 'admin.utilisateurs.*'),
            $lien('admin.trajets.index', 'Trajets', 'admin.trajets.*'),
            $lien('admin.reservations.index', 'Réservations', 'admin.reservations.*'),
            $lien('admin.statistiques', 'Statistiques'),
            $lien('admin.parametres', 'Paramètres'),
        ];
    } else {
        $espace = null;
        $role = null;
        $liens = [
            ['url' => route('home') . '#fonctionnalites', 'actif' => false, 'label' => 'Fonctionnalités'],
            ['url' => route('home') . '#profils', 'actif' => false, 'label' => 'Profils'],
            $lien('trajets.index', 'Rechercher un trajet', 'trajets.*'),
        ];
    }

    $liens = array_values(array_filter($liens));
    $estAdmin = (bool) $user?->estAdmin();
@endphp

<header x-data="{ ouvert: false }" class="sticky top-0 z-40 border-b border-outline-variant/40 bg-surface-container-lowest/90 backdrop-blur-md">
    <div class="mx-auto flex h-16 max-w-[1200px] items-center justify-between gap-4 px-4 sm:px-8">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-1 font-headline-sm text-headline-sm font-extrabold text-primary-container">
                {{ config('app.name') }}<span class="h-2 w-2 rounded-full bg-secondary-container"></span>
            </a>
            @if ($espace)
                <span class="hidden rounded-full bg-primary-fixed px-2.5 py-1 font-label-sm text-label-sm uppercase text-on-primary-fixed-variant sm:inline-block">
                    {{ $espace }}
                </span>
            @endif
        </div>

        @unless ($estAdmin)
            <nav class="hidden items-center gap-1 rounded-xl bg-surface-container-low p-1 md:flex">
                @foreach ($liens as $l)
                    <a href="{{ $l['url'] }}"
                       class="rounded-lg px-3 py-2 font-label-lg text-label-lg transition {{ $l['actif'] ? 'bg-primary-container text-on-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                        {{ $l['label'] }}
                    </a>
                @endforeach
            </nav>
        @endunless

        <div class="flex items-center gap-2">
            @auth
                <div class="hidden items-center gap-2 rounded-xl border border-outline-variant/50 bg-surface-container-lowest py-1.5 pl-1.5 pr-3 sm:flex">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-container font-label-lg text-label-lg text-on-primary">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </span>
                    <div class="leading-tight">
                        <div class="font-label-lg text-label-lg text-on-surface">{{ $user->name }}</div>
                        <div class="font-label-sm text-label-sm text-primary-container">{{ $role }}</div>
                    </div>
                </div>

                <a href="{{ route('profile.edit') }}" title="Mon profil"
                   class="flex h-10 w-10 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container">
                    <x-ui.icon>account_circle</x-ui.icon>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Se déconnecter"
                            class="flex h-10 w-10 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container">
                        <x-ui.icon>logout</x-ui.icon>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}"
                   class="hidden px-3 py-2 font-label-lg text-label-lg text-on-surface-variant hover:text-primary-container sm:inline-block">Connexion</a>
                <x-ui.button :href="route('register')" class="hidden sm:inline-flex">Créer un compte</x-ui.button>
            @endauth

            <button type="button" x-on:click="ouvert = ! ouvert" aria-label="Menu"
                    class="flex h-10 w-10 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container md:hidden">
                <x-ui.icon>menu</x-ui.icon>
            </button>
        </div>
    </div>

    {{-- Onglets de l'administration --}}
    @if ($estAdmin && count($liens) > 1)
        <div class="hidden border-t border-outline-variant/40 md:block">
            <nav class="mx-auto flex max-w-[1200px] gap-1 overflow-x-auto px-4 sm:px-8">
                @foreach ($liens as $l)
                    <a href="{{ $l['url'] }}"
                       class="whitespace-nowrap border-b-2 px-3 py-3 font-label-lg text-label-lg transition {{ $l['actif'] ? 'border-primary-container text-primary-container' : 'border-transparent text-on-surface-variant hover:text-primary-container' }}">
                        {{ $l['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    @endif

    {{-- Menu mobile --}}
    <div x-show="ouvert" x-cloak class="border-t border-outline-variant/40 bg-surface-container-lowest md:hidden">
        <div class="space-y-1 px-4 py-3">
            @foreach ($liens as $l)
                <a href="{{ $l['url'] }}"
                   class="block rounded-lg px-3 py-2.5 font-label-lg text-label-lg {{ $l['actif'] ? 'bg-primary-container text-on-primary' : 'text-on-surface-variant hover:bg-surface-container' }}">
                    {{ $l['label'] }}
                </a>
            @endforeach

            @guest
                <div class="flex gap-2 pt-2">
                    <x-ui.button :href="route('login')" variant="outline" class="flex-1">Connexion</x-ui.button>
                    <x-ui.button :href="route('register')" class="flex-1">Créer un compte</x-ui.button>
                </div>
            @endguest

            @auth
                <div class="border-t border-outline-variant/40 pt-3 font-body-sm text-body-sm text-on-surface-variant">
                    {{ $user->name }} · {{ $role }}
                </div>
            @endauth
        </div>
    </div>
</header>