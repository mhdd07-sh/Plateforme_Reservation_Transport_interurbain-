@if (request()->routeIs('home'))
    <footer class="border-t border-outline-variant/40 bg-surface-container-low">
        <div class="mx-auto grid max-w-[1200px] gap-8 px-4 py-10 sm:px-8 md:grid-cols-3">
            <div>
                <div class="font-headline-sm text-headline-sm font-extrabold text-primary-container">{{ config('app.name') }}</div>
                <p class="mt-2 max-w-xs font-body-md text-body-md text-on-surface-variant">
                    Plateforme de réservation de transport interurbain au Sénégal : Tata, minibus et cars.
                </p>
                <p class="mt-3 font-body-sm text-body-sm text-on-surface-variant">Projet de Génie Logiciel · Polytech Diamniadio</p>
            </div>

            <div>
                <div class="font-label-lg text-label-lg text-on-surface">Navigation</div>
                <ul class="mt-3 space-y-2 font-body-md text-body-md text-on-surface-variant">
                    @if (Route::has('trajets.index'))
                        <li><a href="{{ route('trajets.index') }}" class="hover:text-primary-container">Rechercher un trajet</a></li>
                    @endif
                    <li><a href="{{ route('register') }}" class="hover:text-primary-container">Créer un compte</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-primary-container">Connexion</a></li>
                </ul>
            </div>

            <div>
                <div class="font-label-lg text-label-lg text-on-surface">Paiements supportés</div>
                <p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">Règlement par mobile money.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-ui.badge tone="info">Wave</x-ui.badge>
                    <x-ui.badge tone="warning">Orange Money</x-ui.badge>
                </div>
            </div>
        </div>

        <div class="border-t border-outline-variant/40">
            <div class="mx-auto flex max-w-[1200px] flex-col gap-1 px-4 py-4 font-body-sm text-body-sm text-on-surface-variant sm:flex-row sm:justify-between sm:px-8">
                <span>© {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</span>
                <span>Dakar · Thiès · Saint-Louis · Kaolack · Touba · Ziguinchor</span>
            </div>
        </div>
    </footer>
@else
    <footer class="mt-12 border-t border-outline-variant/40 bg-surface-container-lowest">
        <div class="mx-auto flex max-w-[1200px] flex-col gap-2 px-4 py-5 font-body-sm text-body-sm text-on-surface-variant sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <span>
                <span class="font-headline-sm font-extrabold text-primary-container">{{ config('app.name') }}</span>
                · © {{ date('Y') }} Mobilité interurbaine du Sénégal.
            </span>
            <span class="inline-flex items-center gap-1">
                <x-ui.icon class="text-primary-container">verified_user</x-ui.icon>
                Paiements sécurisés (Wave · Orange Money)
            </span>
        </div>
    </footer>
@endif