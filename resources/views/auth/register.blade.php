<x-guest-layout wide>
    <form method="POST" action="{{ route('register') }}"
          x-data="{
              type: @js(old('type_compte', request('type', 'voyageur'))),
              mdp: '',
              voir: false,
              get force() {
                  let s = 0;
                  if (this.mdp.length >= 8) s++;
                  if (/[A-Z]/.test(this.mdp)) s++;
                  if (/[0-9]/.test(this.mdp)) s++;
                  if (/[^A-Za-z0-9]/.test(this.mdp)) s++;
                  return s;
              },
              get libelle() { return ['Faible', 'Faible', 'Moyen', 'Bon', 'Excellent'][this.force]; }
          }">
        @csrf

        <p class="inline-flex items-center gap-1 font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">
            <x-ui.icon class="text-primary-container">verified_user</x-ui.icon>
            Plateforme officielle · Sénégal
        </p>
        <h1 class="mt-2 font-display-hero-mobile text-display-hero-mobile sm:font-display-hero sm:text-display-hero">Créer un compte</h1>
        <p class="mt-2 max-w-2xl font-body-lg text-body-lg text-on-surface-variant">
            Rejoignez la plateforme de transport interurbain du Sénégal. Simplifiez vos trajets entre Dakar, Thiès, Saint-Louis et toutes les régions.
        </p>

        {{-- Étape 1 : type de compte --}}
        <h2 class="mt-10 font-headline-sm text-headline-sm">Vous êtes :</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach ([
                ['voyageur', 'Un voyageur', 'directions_walk', 'Je cherche et je réserve un trajet en toute sérénité.', 'Recommandé'],
                ['particulier', 'Un transporteur particulier', 'directions_car', "Je publie mes trajets. Une pièce d'identité me sera demandée après l'inscription.", null],
                ['compagnie', 'Une compagnie', 'directions_bus', 'Je gère mes véhicules, mes trajets et mes départs réguliers.', null],
            ] as [$valeur, $titre, $icone, $texte, $etiquette])
                <label class="relative block cursor-pointer rounded-xl bg-surface-container-lowest p-5 transition"
                       x-bind:class="type === '{{ $valeur }}' ? 'border-2 border-primary-container shadow-elevated' : 'border border-outline-variant/60 hover:border-primary-container'">
                    <input type="radio" name="type_compte" value="{{ $valeur }}" x-model="type" class="sr-only">

                    @if ($etiquette)
                        <span class="absolute -top-3 right-4 rounded-full bg-primary-container px-3 py-0.5 font-label-sm text-label-sm text-on-primary">{{ $etiquette }}</span>
                    @endif

                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-outline-variant"
                                  x-bind:class="type === '{{ $valeur }}' ? '!border-primary-container' : ''">
                                <span class="h-2.5 w-2.5 rounded-full bg-primary-container" x-show="type === '{{ $valeur }}'"></span>
                            </span>
                            <span class="font-headline-sm text-headline-sm">{{ $titre }}</span>
                        </div>
                        <x-ui.icon class="text-on-surface-variant">{{ $icone }}</x-ui.icon>
                    </div>
                    <p class="mt-3 font-body-md text-body-md text-on-surface-variant">{{ $texte }}</p>
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('type_compte')" class="mt-2" />

        {{-- Étape 2 : informations --}}
        <div class="mt-8 rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-card sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-headline-md text-headline-md">Informations personnelles</h2>
                    <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
                        Ces informations serviront à l'édition de votre billet de transport nominatif.
                    </p>
                </div>
                <span class="hidden items-center gap-1 rounded-full bg-surface-container px-3 py-1 font-label-sm text-label-sm text-on-surface-variant sm:inline-flex">
                    <x-ui.icon>lock</x-ui.icon> Sécurisé
                </span>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <x-input-label for="prenom" value="Prénom *" />
                    <x-text-input id="prenom" name="prenom" type="text" class="mt-1" :value="old('prenom')" placeholder="ex : Fatou" required autocomplete="given-name" />
                    <x-input-error :messages="$errors->get('prenom')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nom" value="Nom *" />
                    <x-text-input id="nom" name="nom" type="text" class="mt-1" :value="old('nom')" placeholder="ex : Sow" required autocomplete="family-name" />
                    <x-input-error :messages="$errors->get('nom')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="E-mail *" />
                    <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email')" placeholder="fatou.sow@domaine.sn" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                                <div>
                    <div class="flex items-baseline justify-between">
                        <x-input-label for="telephone" value="Téléphone *" />
                        <span class="font-label-sm text-label-sm text-on-surface-variant"
                        x-text="type === 'compagnie' ? 'Mobile ou fixe' : 'Wave / Orange Money'">Wave / Orange Money</span>
                    </div>
                    <div class="mt-1 flex">
                        <span class="inline-flex min-h-[48px] items-center rounded-l-lg border border-r-0 border-[#D1D5DB] bg-surface-container px-3 font-label-lg text-label-lg text-on-surface-variant">+221</span>
                        <x-text-input id="telephone" name="telephone" type="tel" class="rounded-l-none"
                            :value="old('telephone')" placeholder="77 000 00 00"
                            x-bind:placeholder="type === 'compagnie' ? '77 000 00 00 ou 33 800 00 00' : '77 000 00 00'"
                            required autocomplete="tel" />
                    </div>
                    <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
                </div>

                {{-- Champs réservés aux compagnies --}}
                <div x-show="type === 'compagnie'" x-cloak>
                    <x-input-label for="nom_compagnie" value="Nom de la compagnie *" />
                    <x-text-input id="nom_compagnie" name="nom_compagnie" type="text" class="mt-1" :value="old('nom_compagnie')" placeholder="ex : Sahel Voyage" />
                    <x-input-error :messages="$errors->get('nom_compagnie')" class="mt-2" />
                </div>

                <div x-show="type === 'compagnie'" x-cloak>
                    <x-input-label for="numero_agrement" value="Numéro d'agrément *" />
                    <x-text-input id="numero_agrement" name="numero_agrement" type="text" class="mt-1" :value="old('numero_agrement')" placeholder="AGR-000000" />
                    <x-input-error :messages="$errors->get('numero_agrement')" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <div class="flex items-baseline justify-between">
                        <x-input-label for="password" value="Mot de passe *" />
                        <span class="font-label-sm text-label-sm text-on-surface-variant">Min. 8 caractères</span>
                    </div>
                    <div class="relative mt-1">
                        <x-text-input id="password" name="password" type="password" x-bind:type="voir ? 'text' : 'password'"
                            x-model="mdp" class="pr-11" placeholder="••••••••" required autocomplete="new-password" />
                        <button type="button" x-on:click="voir = ! voir" aria-label="Afficher le mot de passe"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface">
                            <x-ui.icon>visibility</x-ui.icon>
                        </button>
                    </div>
                    <div class="mt-2 flex items-center gap-3">
                        <div class="grid flex-1 grid-cols-4 gap-2">
                            @foreach ([1, 2, 3, 4] as $barre)
                                <span class="h-1.5 rounded-full"
                                      x-bind:class="force >= {{ $barre }} ? (force >= 3 ? 'bg-primary-container' : 'bg-secondary-container') : 'bg-surface-container-high'"></span>
                            @endforeach
                        </div>
                        <span class="w-20 text-right font-label-sm text-label-sm text-on-surface-variant" x-text="mdp.length ? libelle : ''"></span>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>

            <label class="mt-6 flex items-start gap-3">
                <input type="checkbox" name="accepte_cgu" value="1" @checked(old('accepte_cgu'))
                       class="mt-0.5 rounded border-[#D1D5DB] text-primary-container focus:ring-primary-container/30">
                <span class="font-body-md text-body-md text-on-surface-variant">
                    J'accepte les <span class="font-label-lg text-label-lg text-primary-container">Conditions Générales d'Utilisation</span>
                    et la <span class="font-label-lg text-label-lg text-primary-container">politique de confidentialité</span> de {{ config('app.name') }}.
                </span>
            </label>
            <x-input-error :messages="$errors->get('accepte_cgu')" class="mt-2" />
        </div>

        <div class="mt-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
            <p class="font-body-md text-body-md text-on-surface-variant">
                Vous avez déjà un compte ?
                <a href="{{ route('login') }}" class="font-label-lg text-label-lg text-primary-container underline-offset-4 hover:underline">Se connecter</a>
            </p>
            <x-primary-button class="w-full sm:w-auto">
                Créer mon compte
                <x-ui.icon>arrow_forward</x-ui.icon>
            </x-primary-button>
        </div>

        <p class="mt-8 text-center font-body-sm text-body-sm text-on-surface-variant" x-show="type !== 'voyageur'" x-cloak>
            Les comptes transporteur sont validés par l'administrateur avant de pouvoir publier des trajets.
        </p>
    </form>
</x-guest-layout>