<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="immatriculation" value="Immatriculation" />
        <x-text-input id="immatriculation" name="immatriculation" type="text" class="mt-1 block w-full uppercase"
            :value="old('immatriculation', $vehicule->immatriculation)" placeholder="DK-1234-AB" required />
        <x-input-error :messages="$errors->get('immatriculation')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="marque" value="Marque" />
        <x-text-input id="marque" name="marque" type="text" class="mt-1 block w-full"
            :value="old('marque', $vehicule->marque)" placeholder="Toyota" required />
        <x-input-error :messages="$errors->get('marque')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="type_vehicule" value="Type de véhicule" />
        <select id="type_vehicule" name="type_vehicule" required
            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach (\App\Models\Vehicule::TYPES as $type)
                <option value="{{ $type }}" @selected(old('type_vehicule', $vehicule->type_vehicule) === $type)>
                    {{ ucfirst($type) }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('type_vehicule')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="capacite" value="Capacité (nombre de places)" />
        <x-text-input id="capacite" name="capacite" type="number" min="1" max="100" class="mt-1 block w-full"
            :value="old('capacite', $vehicule->capacite)" required />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Les sièges numérotés de 1 à N sont créés automatiquement.
        </p>
        <x-input-error :messages="$errors->get('capacite')" class="mt-2" />
    </div>
</div>