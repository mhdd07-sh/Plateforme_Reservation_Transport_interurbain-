<x-guest-layout>
    <div class="text-center">
        <h1 class="font-display-hero-mobile text-display-hero-mobile">Nouveau mot de passe</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Choisissez un mot de passe d'au moins 8 caractères.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}"
          class="mt-6 space-y-5 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Nouveau mot de passe" />
            <x-text-input id="password" name="password" type="password" class="mt-1" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Réinitialiser le mot de passe</x-primary-button>
    </form>
</x-guest-layout>