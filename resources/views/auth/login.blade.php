<x-guest-layout>
    <div class="text-center">
        <span class="inline-flex items-center gap-2 rounded-full bg-surface-container px-3 py-1 font-label-md text-label-md text-on-surface">
            <span class="h-2 w-2 rounded-full bg-primary-container"></span>
            Réseau interurbain Sénégal
        </span>
        <h1 class="mt-4 font-display-hero-mobile text-display-hero-mobile">Connexion</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Accédez à vos trajets, billets et réservations</p>
    </div>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}"
          x-data="{ voir: false }"
          class="mt-6 space-y-5 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
        @csrf

        <div>
            <div class="flex items-baseline justify-between">
                <x-input-label for="identifiant" value="E-mail ou téléphone" />
                <span class="font-label-sm text-label-sm text-on-surface-variant">Sénégal (+221)</span>
            </div>
            <div class="relative mt-1">
                <x-ui.icon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-outline">alternate_email</x-ui.icon>
                <x-text-input id="identifiant" name="identifiant" type="text" class="pl-11"
                    :value="old('identifiant')" placeholder="ex : amadou.diallo@email.sn ou 77 123 45 67"
                    required autofocus autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('identifiant')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Mot de passe" />
            <div class="relative mt-1">
                <x-ui.icon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-outline">lock</x-ui.icon>
                <x-text-input id="password" name="password" x-bind:type="voir ? 'text' : 'password'" type="password"
                    class="px-11" placeholder="Entrez votre mot de passe" required autocomplete="current-password" />
                <button type="button" x-on:click="voir = ! voir" aria-label="Afficher le mot de passe"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface">
                    <x-ui.icon>visibility</x-ui.icon>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2">
            <input id="remember_me" type="checkbox" name="remember"
                   class="rounded border-[#D1D5DB] text-primary-container focus:ring-primary-container/30">
            <span class="font-body-md text-body-md text-on-surface-variant">Se souvenir de moi</span>
        </label>

        <x-primary-button class="w-full">
            Se connecter
            <x-ui.icon>arrow_forward</x-ui.icon>
        </x-primary-button>

        @if (Route::has('password.request'))
            <div class="text-center">
                <a href="{{ route('password.request') }}" class="font-label-lg text-label-lg text-primary-container underline-offset-4 hover:underline">
                    Mot de passe oublié ?
                </a>
            </div>
        @endif
    </form>

    <p class="mt-6 text-center font-body-md text-body-md text-on-surface-variant">
        Pas encore de compte ?
        <a href="{{ route('register') }}" class="font-label-lg text-label-lg text-primary-container underline-offset-4 hover:underline">Créer un compte</a>
    </p>

    <p class="mt-6 flex items-center justify-center gap-1 text-center font-body-sm text-body-sm text-on-surface-variant">
        <x-ui.icon class="text-primary-container">verified_user</x-ui.icon>
        Connexion sécurisée · Vos données sont protégées
    </p>
    <div class="mt-3 flex items-center justify-center gap-4 font-label-sm text-label-sm text-on-surface-variant">
        <span>Wave</span><span>·</span><span>Orange Money</span>
    </div>
</x-guest-layout>