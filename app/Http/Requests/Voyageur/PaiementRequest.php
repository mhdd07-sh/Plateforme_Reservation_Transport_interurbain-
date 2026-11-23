<?php

namespace App\Http\Requests\Voyageur;

use App\Models\Paiement;
use App\Support\Telephone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estVoyageur() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $numero = (string) $this->input('numero');

        $this->merge([
            'numero' => $this->input('moyen') === Paiement::MOYEN_CARTE
                ? preg_replace('/\D/', '', $numero)
                : Telephone::normaliser($numero),
        ]);
    }

    public function rules(): array
    {
        $carte = $this->input('moyen') === Paiement::MOYEN_CARTE;

        return [
            'moyen' => ['required', Rule::in(Paiement::MOYENS)],
            'numero' => ['required', $carte ? 'digits_between:13,19' : 'regex:/^(70|75|76|77|78)[0-9]{7}$/'],
            'accepte_cgv' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'moyen.required' => 'Choisissez un mode de paiement.',
            'moyen.in' => 'Mode de paiement invalide.',
            'numero.required' => 'Saisissez votre numéro.',
            'numero.regex' => 'Entrez un numéro sénégalais valide (ex. 77 123 45 67).',
            'numero.digits_between' => 'Le numéro de carte doit comporter entre 13 et 19 chiffres.',
            'accepte_cgv.accepted' => 'Vous devez accepter les conditions générales de vente.',
        ];
    }
}