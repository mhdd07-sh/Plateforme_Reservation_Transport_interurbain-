<?php

namespace App\Http\Requests\Transporteur;

use App\Models\Vehicule;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TrajetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->transporteur?->estValide() ?? false;
    }

    public function rules(): array
    {
        $transporteurId = $this->user()->transporteur->id;

        return [
            // Le véhicule doit appartenir au transporteur connecté.
            'vehicule_id' => [
                'required',
                Rule::exists('vehicules', 'id')->where('transporteur_id', $transporteurId),
            ],
            'ville_depart' => ['required', 'string', 'max:80'],
            'ville_arrivee' => ['required', 'string', 'max:80', 'different:ville_depart'],
            'date_depart' => ['required', 'date', 'after_or_equal:today'],
            'heure_depart' => ['required', 'date_format:H:i'],
            'prix_unitaire' => ['required', 'integer', 'min:100', 'max:1000000'],
        ];
    }

    /** Règles métier du diagramme de séquence 6.3 : départ futur + véhicule disponible. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $date = $this->input('date_depart');
            $heure = $this->input('heure_depart');

            if (Carbon::parse($date . ' ' . $heure)->lte(now())) {
                $validator->errors()->add('heure_depart', 'Le départ doit être dans le futur.');

                return;
            }

            $vehicule = Vehicule::find($this->input('vehicule_id'));
            $ignorer = $this->route('trajet')?->id;

            if (! $vehicule->estDisponible($date, $heure, $ignorer)) {
                $validator->errors()->add(
                    'vehicule_id',
                    'Ce véhicule est déjà affecté à un trajet à cette date et à cette heure.'
                );
            }
        });
    }

    public function attributes(): array
    {
        return [
            'vehicule_id' => 'véhicule',
            'ville_depart' => 'ville de départ',
            'ville_arrivee' => "ville d'arrivée",
            'date_depart' => 'date de départ',
            'heure_depart' => 'heure de départ',
            'prix_unitaire' => 'prix unitaire',
        ];
    }

    public function messages(): array
    {
        return [
            'ville_arrivee.different' => "La ville d'arrivée doit être différente de la ville de départ.",
            'vehicule_id.exists' => 'Ce véhicule ne vous appartient pas.',
        ];
    }
}