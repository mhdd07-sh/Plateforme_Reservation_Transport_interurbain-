<x-app-layout>
    <div class="mx-auto max-w-[640px] space-y-6 px-4 py-8 sm:px-8">
        <x-voyageur.etapes :actuelle="3" />

        <form method="POST" action="{{ route('voyageur.paiement.confirmer', $paiement) }}"
              class="space-y-5 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card sm:p-8">
            @csrf

            <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-primary-fixed text-on-primary-fixed-variant">
                <x-ui.icon>sms</x-ui.icon>
            </div>

            <div>
                <h1 class="font-headline-lg-mobile text-headline-lg-mobile">Confirmez votre paiement</h1>
                <p class="mt-2 font-body-md text-body-md text-on-surface-variant">
                    Un code à 6 chiffres vient d'être envoyé pour valider
                    <strong>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong>
                    avec {{ \App\Models\Paiement::libelleMoyen($paiement->moyen_paiement) }}
                    ({{ $paiement->numero_paiement }}).
                </p>
            </div>

            @if (config('paiement.driver') === 'fake')
                <div class="flex items-start gap-3 rounded-lg bg-secondary-fixed p-3 font-body-md text-body-md text-on-secondary-fixed">
                    <x-ui.icon>science</x-ui.icon>
                    <span>Mode démonstration : saisissez le code de test <strong>{{ config('paiement.fake.code_valide') }}</strong>.</span>
                </div>
            @endif

            <div>
                <x-input-label for="code" value="Code de confirmation" />
                <x-text-input id="code" name="code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                    class="mt-1 text-center font-headline-md text-headline-md tracking-[0.5em]" placeholder="000000" required autofocus />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <x-primary-button class="w-full">Confirmer le paiement</x-primary-button>

            <p class="text-center font-body-md text-body-md">
                <a href="{{ route('voyageur.paiement.create', $reservation) }}" class="font-label-lg text-label-lg text-primary-container hover:underline">
                    Changer de moyen de paiement
                </a>
            </p>
        </form>
    </div>
</x-app-layout>