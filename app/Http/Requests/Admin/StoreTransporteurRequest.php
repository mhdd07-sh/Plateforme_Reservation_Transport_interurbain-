<?php

namespace App\Http\Requests\Admin;

use App\Models\Transporteur;
use App\Support\Telephone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class StoreTransporteurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'telephone' => Telephone::normaliser((string) $this->input('telephone')),
            'valider_immediatement' => $this->boolean('valider_immediatement'),
        ]);
    }

    public function rules(): array
    {
        $compagnie = $this->input('type') === Transporteur::TYPE_COMPAGNIE;
    
        return [
            'type' => ['required', Rule::in([Transporteur::TYPE_PARTICULIER, Transporteur::TYPE_COMPAGNIE])],
            'nom_responsable' => ['required', 'string', 'max:255'],
            'nom_compagnie' => [$compagnie ? 'required' : 'nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['required', 'regex:' . Telephone::regex($compagnie), 'unique:users,telephone'],
            'numero_agrement' => [
                $compagnie ? 'required' : 'nullable', 'string', 'max:50',
                Rule::unique('transporteurs', 'numero_agrement'),
            ],
            'password' => ['required', Rules\Password::min(8)],
            'valider_immediatement' => ['boolean'],
        ];
    }

      public function messages(): array
    {
        $compagnie = $this->input('type') === Transporteur::TYPE_COMPAGNIE;

        return [
            'nom_responsable.required' => 'Indiquez le nom du responsable.',
            'nom_compagnie.required' => 'Indiquez le nom de la compagnie.',
            'telephone.regex' => $compagnie
                ? 'Entrez un numéro sénégalais valide : mobile (70, 75, 76, 77, 78) ou fixe (33).'
                : 'Entrez un numéro mobile sénégalais valide (70, 75, 76, 77 ou 78).',
            'telephone.unique' => 'Ce numéro est déjà utilisé.',
            'numero_agrement.required' => "Indiquez le numéro d'agrément.",
            'numero_agrement.unique' => "Ce numéro d'agrément existe déjà.",
        ];
    }
}