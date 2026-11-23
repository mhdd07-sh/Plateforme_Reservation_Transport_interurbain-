<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input-label for="vehicule_id" value="Véhicule" />
        <select id="vehicule_id" name="vehicule_id" required
            class="mt-1 block min-h-[48px] w-full rounded-lg border border-[#D1D5DB] bg-white px-4 font-body-md text-body-md focus:border-primary-container focus:ring-2 focus:ring-primary-container/15">
            <option value="">Choisir un véhicule</option>
            @foreach ($vehicules as $v)
                <option value="{{ $v->id }}"
                    @selected((string) old('vehicule_id', $trajet->vehicule_id) === (string) $v->id)
                    @disabled(! $v->estActif())>
                    {{ $v->immatriculation }} — {{ \App\Models\Vehicule::libelleType($v->type_vehicule) }} ({{ $v->capacite }} places){{ $v->estActif() ? '' : ' — en maintenance' }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('vehicule_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="ville_depart" value="Ville de départ" />
        <x-text-input id="ville_depart" name="ville_depart" type="text" list="villes" class="mt-1"
            :value="old('ville_depart', $trajet->ville_depart)" placeholder="Ex : Dakar, Thiaroye..." required />
        <x-input-error :messages="$errors->get('ville_depart')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="ville_arrivee" value="Ville d'arrivée" />
        <x-text-input id="ville_arrivee" name="ville_arrivee" type="text" list="villes" class="mt-1"
            :value="old('ville_arrivee', $trajet->ville_arrivee)" placeholder="Ex : Saint-Louis, Touba..." required />
        <x-input-error :messages="$errors->get('ville_arrivee')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="date_depart" value="Date de départ" />
        <x-text-input id="date_depart" name="date_depart" type="date" min="{{ now()->toDateString() }}" class="mt-1"
            :value="old('date_depart', $trajet->date_depart?->format('Y-m-d'))" required />
        <x-input-error :messages="$errors->get('date_depart')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="heure_depart" value="Heure de départ" />
        <x-text-input id="heure_depart" name="heure_depart" type="time" class="mt-1"
            :value="old('heure_depart', $trajet->heure_depart ? substr($trajet->heure_depart, 0, 5) : '')" required />
        <x-input-error :messages="$errors->get('heure_depart')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="prix_unitaire" value="Prix par place (FCFA)" />
        <x-text-input id="prix_unitaire" name="prix_unitaire" type="number" min="100" step="50" class="mt-1"
            :value="old('prix_unitaire', $trajet->prix_unitaire)" placeholder="5000" required />
        <x-input-error :messages="$errors->get('prix_unitaire')" class="mt-2" />
    </div>
</div>

<datalist id="villes">
    @foreach (config('transport.villes', []) as $ville)
        <option value="{{ $ville }}"></option>
    @endforeach
</datalist>