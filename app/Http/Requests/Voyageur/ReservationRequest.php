<?php

namespace App\Http\Requests\Voyageur;

use App\Services\ReservationService;
use Illuminate\Foundation\Http\FormRequest;

class ReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estVoyageur() ?? false;
    }

    public function rules(): array
    {
        return [
            'sieges' => ['required', 'array', 'min:1', 'max:' . ReservationService::MAX_SIEGES_PAR_RESERVATION],
            'sieges.*' => ['integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'sieges.required' => 'Choisissez au moins un siège.',
            'sieges.min' => 'Choisissez au moins un siège.',
            'sieges.max' => 'Vous pouvez choisir au maximum ' . ReservationService::MAX_SIEGES_PAR_RESERVATION . ' sièges.',
            'sieges.*.integer' => 'Siège invalide.',
            'sieges.*.distinct' => 'Un siège ne peut être choisi qu\'une fois.',
        ];
    }
}