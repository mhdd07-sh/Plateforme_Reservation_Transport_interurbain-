<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-primary-fixed text-on-primary-fixed-variant">
            <x-ui.icon>mark_email_unread</x-ui.icon>
        </div>
        <h1 class="mt-4 font-display-hero-mobile text-display-hero-mobile">Vérifiez votre e-mail</h1>
        <p class="mt-2 font-body-md text-body-md text-on-surface-variant">
            Un lien de confirmation a été envoyé à <strong>{{ auth()->user()->email }}</strong>.
            Cliquez dessus pour activer votre compte.
        </p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="mt-6 flex items-center gap-2 rounded-lg bg-primary-fixed px-4 py-3 font-body-md text-body-md text-on-primary-fixed-variant">
            <x-ui.icon>check_circle</x-ui.icon>
            Un nouveau lien de confirmation vient d'être envoyé.
        </div>
    @endif

    @if (app()->environment('local') && config('mail.default') === 'log')
        <div class="mt-6 rounded-lg bg-secondary-fixed px-4 py-3 font-body-md text-body-md text-on-secondary-fixed">
            <strong>Mode développement.</strong> Aucun e-mail n'est réellement envoyé : il est écrit dans
            <code>storage/logs/laravel.log</code>. Lancez <code>php artisan mail:lien</code> pour afficher le lien.
        </div>
    @endif

    <div class="mt-6 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full">Renvoyer le lien de confirmation</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-center font-label-lg text-label-lg text-on-surface-variant hover:underline">
                Se déconnecter
            </button>
        </form>
    </div>
</x-guest-layout>