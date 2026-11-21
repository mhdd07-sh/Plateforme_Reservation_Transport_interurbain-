<?php

namespace App\Http\Requests\Transporteur;

use App\Models\Vehicule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehiculeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->transporteur?->estValide() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'immatriculation' => strtoupper(trim((string) $this->input('immatriculation'))),
        ]);
    }

    public function rules(): array
    {
        $vehicule = $this->route('vehicule');

        return [
            'immatriculation' => [
                'required', 'string', 'max:20',
                Rule::unique('vehicules', 'immatriculation')->ignore($vehicule?->id),
            ],
            'marque' => ['required', 'string', 'max:50'],
            'type_vehicule' => ['required', Rule::in(Vehicule::TYPES)],
            'capacite' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'immatriculation' => 'immatriculation',
            'marque' => 'marque',
            'type_vehicule' => 'type de véhicule',
            'capacite' => 'capacité',
        ];
    }

    public function messages(): array
    {
        return [
            'immatriculation.unique' => 'Cette immatriculation est déjà enregistrée.',
            'type_vehicule.in' => 'Le type de véhicule choisi est invalide.',
        ];
    }
}