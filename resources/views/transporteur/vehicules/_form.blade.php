<div class="grid grid-cols-1 gap-6 sm:grid-cols-2" x-data="{ statut: @js(old('statut', $vehicule->statut ?? 'actif')) }">
    <div>
        <x-input-label for="immatriculation" value="Immatriculation" />
        <x-text-input id="immatriculation" name="immatriculation" type="text" class="mt-1 uppercase"
            :value="old('immatriculation', $vehicule->immatriculation)" placeholder="DK-1234-AB" required />
        <x-input-error :messages="$errors->get('immatriculation')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="marque" value="Marque" />
        <x-text-input id="marque" name="marque" type="text" class="mt-1"
            :value="old('marque', $vehicule->marque)" placeholder="Toyota" required />
        <x-input-error :messages="$errors->get('marque')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <div class="font-label-lg text-label-lg">Type de véhicule</div>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach (\App\Models\Vehicule::TYPES as $type)
                <label class="cursor-pointer">
                    <input type="radio" name="type_vehicule" value="{{ $type }}" class="peer sr-only"
                           @checked(old('type_vehicule', $vehicule->type_vehicule ?? 'minibus') === $type)>
                    <span class="inline-flex min-h-[44px] items-center rounded-lg border border-outline-variant/60 px-5 font-label-lg text-label-lg text-on-surface-variant transition peer-checked:border-primary-container peer-checked:bg-primary-container peer-checked:text-on-primary">
                        {{ \App\Models\Vehicule::libelleType($type) }}
                    </span>
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('type_vehicule')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="capacite" value="Capacité (nombre de places)" />
        <x-text-input id="capacite" name="capacite" type="number" min="1" max="100" class="mt-1 sm:max-w-xs"
            :value="old('capacite', $vehicule->capacite)" required />
        <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">Les sièges numérotés de 1 à N sont créés automatiquement.</p>
        <x-input-error :messages="$errors->get('capacite')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <div class="font-label-lg text-label-lg">Statut</div>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach (['actif' => 'Actif', 'en_maintenance' => 'En maintenance'] as $valeur => $libelle)
                <label class="cursor-pointer">
                    <input type="radio" name="statut" value="{{ $valeur }}" x-model="statut" class="peer sr-only">
                    <span class="inline-flex min-h-[44px] items-center rounded-lg border border-outline-variant/60 px-5 font-label-lg text-label-lg text-on-surface-variant transition peer-checked:border-primary-container peer-checked:bg-primary-container peer-checked:text-on-primary">
                        {{ $libelle }}
                    </span>
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('statut')" class="mt-2" />
    </div>

    <div class="sm:col-span-2" x-show="statut === 'en_maintenance'" x-cloak>
        <x-input-label for="note_maintenance" value="Note de maintenance" />
        <textarea id="note_maintenance" name="note_maintenance" rows="3" maxlength="500"
            placeholder="Ex : Révision des freins, garage de Diamniadio"
            class="mt-1 block w-full rounded-lg border border-[#D1D5DB] bg-white px-4 py-3 font-body-md text-body-md focus:border-primary-container focus:ring-2 focus:ring-primary-container/15">{{ old('note_maintenance', $vehicule->note_maintenance) }}</textarea>
        <x-input-error :messages="$errors->get('note_maintenance')" class="mt-2" />
    </div>
</div>