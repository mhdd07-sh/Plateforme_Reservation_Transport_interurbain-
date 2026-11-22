<x-guest-layout>
    <div class="text-center">
        <h1 class="font-display-hero-mobile text-display-hero-mobile">Mot de passe oublié</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
            Indiquez votre adresse e-mail : nous vous enverrons un lien pour choisir un nouveau mot de passe.
        </p>
    </div>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}"
          class="mt-6 space-y-5 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card">
        @csrf

        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Envoyer le lien</x-primary-button>
    </form>

    <p class="mt-6 text-center font-body-md text-body-md">
        <a href="{{ route('login') }}" class="font-label-lg text-label-lg text-primary-container underline-offset-4 hover:underline">Retour à la connexion</a>
    </p>
</x-guest-layout>