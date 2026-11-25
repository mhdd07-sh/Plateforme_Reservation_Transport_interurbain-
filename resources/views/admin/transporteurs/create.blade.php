<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-8">
        <a href="{{ route('admin.transporteurs.index') }}" class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary-container hover:underline">
            <x-ui.icon>arrow_back</x-ui.icon> Retour à la liste des transporteurs
        </a>

        <x-page-header titre="Ajouter un transporteur" surtitre="Administration"
            soustitre="Enregistrez un nouveau partenaire de transport et configurez son accès d'exploitation." />

        <form method="POST" action="{{ route('admin.transporteurs.store') }}"
              x-data="{
                  type: @js(old('type', 'compagnie')),
                  mdp: @js(old('password', '')),
                  voir: false,
                  generer() {
                      const caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789#$%&*';
                      const tirage = new Uint32Array(14);
                      crypto.getRandomValues(tirage);
                      this.mdp = Array.from(tirage, n => caracteres[n % caracteres.length]).join('');
                      this.voir = true;
                  }
              }"
              class="overflow-hidden rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-card">
            @csrf

            <div class="h-1.5 bg-primary-container"></div>

            <div class="space-y-8 p-6 sm:p-8">
                <h2 class="inline-flex items-center gap-2 font-headline-sm text-headline-sm">
                    <x-ui.icon class="text-primary-container">badge</x-ui.icon> Identité administrative et représentation légale
                </h2>

                <div>
                    <div class="font-label-lg text-label-lg">Type de transporteur *</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (['compagnie' => 'Compagnie', 'particulier' => 'Transporteur particulier'] as $valeur => $libelle)
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="{{ $valeur }}" x-model="type" class="peer sr-only">
                                <span class="inline-flex min-h-[44px] items-center rounded-lg border border-outline-variant/60 px-5 font-label-lg text-label-lg text-on-surface-variant transition peer-checked:border-primary-container peer-checked:bg-primary-container peer-checked:text-on-primary">{{ $libelle }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="nom_responsable" value="Nom du responsable légal *" />
                        <x-text-input id="nom_responsable" name="nom_responsable" type="text" class="mt-1" :value="old('nom_responsable')" placeholder="Directeur / Gérant" required />
                        <x-input-error :messages="$errors->get('nom_responsable')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nom_compagnie" value="Nom de la compagnie / raison sociale" />
                        <x-text-input id="nom_compagnie" name="nom_compagnie" type="text" class="mt-1" :value="old('nom_compagnie')" placeholder="Ex : Baol Express Interurbain" />
                        <x-input-error :messages="$errors->get('nom_compagnie')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Adresse e-mail professionnelle *" />
                        <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email')" placeholder="direction@compagnie.sn" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                                        <div>
                        <div class="flex items-baseline justify-between">
                            <x-input-label for="telephone" value="Téléphone professionnel *" />
                            <span class="font-label-sm text-label-sm text-on-surface-variant"
                            x-text="type === 'compagnie' ? 'Mobile ou fixe' : 'Wave / Orange Money'">Mobile ou fixe</span>
                        </div>
                        <div class="mt-1 flex">
                            <span class="inline-flex min-h-[48px] items-center rounded-l-lg border border-r-0 border-[#D1D5DB] bg-surface-container px-3 font-label-lg text-label-lg text-on-surface-variant">+221</span>
                            <x-text-input id="telephone" name="telephone" type="tel" class="rounded-l-none" :value="old('telephone')"
                                placeholder="77 000 00 00"
                                x-bind:placeholder="type === 'compagnie' ? '77 000 00 00 ou 33 800 00 00' : '77 000 00 00'" required />
                        </div>
                        <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2" x-show="type === 'compagnie'">
                        <x-input-label for="numero_agrement" value="Numéro d'agrément ministériel *" />
                        <x-text-input id="numero_agrement" name="numero_agrement" type="text" class="mt-1 md:max-w-sm" :value="old('numero_agrement')" placeholder="AGR-000000" />
                        <x-input-error :messages="$errors->get('numero_agrement')" class="mt-2" />
                    </div>
                </div>

                {{-- Accès --}}
                <div class="rounded-xl bg-surface-container-low p-5">
                    <h2 class="inline-flex items-center gap-2 font-headline-sm text-headline-sm">
                        <x-ui.icon class="text-primary-container">lock_reset</x-ui.icon> Accès d'exploitation et identifiants initiaux
                    </h2>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <div class="relative min-w-[240px] flex-1">
                            <x-ui.icon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-outline">key</x-ui.icon>
                            <x-text-input id="password" name="password" type="password" x-bind:type="voir ? 'text' : 'password'"
                                x-model="mdp" class="px-11" placeholder="Mot de passe provisoire (8 caractères min.)" required autocomplete="new-password" />
                            <button type="button" x-on:click="voir = ! voir" aria-label="Afficher le mot de passe" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface">
                                <x-ui.icon>visibility</x-ui.icon>
                            </button>
                        </div>
                        <x-ui.button type="button" variant="light" x-on:click="generer()"><x-ui.icon>autorenew</x-ui.icon> Générer un mot de passe sécurisé</x-ui.button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />

                    <p class="mt-3 flex items-start gap-2 font-body-sm text-body-sm text-on-surface-variant">
                        <x-ui.icon>info</x-ui.icon>
                        Le mot de passe sera affiché une seule fois après la création : transmettez-le au transporteur et invitez-le à le modifier depuis son profil.
                    </p>
                </div>

                <label class="flex items-start justify-between gap-4 rounded-xl border border-outline-variant/40 p-4">
                    <span>
                        <span class="block font-label-lg text-label-lg">Valider le compte immédiatement</span>
                        <span class="block font-body-sm text-body-sm text-on-surface-variant">
                            Si activé, la compagnie peut déclarer ses véhicules et programmer ses départs sans passer par la file d'attente de validation.
                        </span>
                    </span>
                    <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                        <input type="checkbox" name="valider_immediatement" value="1" class="peer sr-only" @checked(old('valider_immediatement', true))>
                        <span class="h-6 w-11 rounded-full bg-surface-container-high transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition after:content-[''] peer-checked:bg-primary-container peer-checked:after:translate-x-5"></span>
                    </span>
                </label>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-outline-variant/40 bg-surface-container-low px-6 py-4 sm:px-8">
                <a href="{{ route('admin.transporteurs.index') }}" class="px-3 font-label-lg text-label-lg text-on-surface-variant hover:underline">Annuler</a>
                <x-primary-button><x-ui.icon>check_circle</x-ui.icon> Créer le compte transporteur</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>