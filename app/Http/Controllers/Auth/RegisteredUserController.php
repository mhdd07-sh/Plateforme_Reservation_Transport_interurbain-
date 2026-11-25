<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Transporteur;
use App\Models\User;
use App\Support\Telephone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Trois types de compte :
     *  - voyageur ;
     *  - particulier / compagnie : compte TRANSPORTEUR créé « en attente » (l'administrateur le valide).
     * Le rôle est affecté ici, jamais lu depuis le formulaire.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'telephone' => Telephone::normaliser((string) $request->input('telephone')),
        ]);

        // Une compagnie peut renseigner un numéro fixe ; les autres comptes doivent avoir un mobile.
        $accepterFixe = $request->input('type_compte') === Transporteur::TYPE_COMPAGNIE;

        $donnees = $request->validate([
            'type_compte' => ['required', Rule::in(['voyageur', Transporteur::TYPE_PARTICULIER, Transporteur::TYPE_COMPAGNIE])],
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'telephone' => ['required', 'regex:' . Telephone::regex($accepterFixe), 'unique:users,telephone'],
            'password' => ['required', Rules\Password::min(8)],
            'accepte_cgu' => ['accepted'],
            'nom_compagnie' => ['nullable', 'required_if:type_compte,' . Transporteur::TYPE_COMPAGNIE, 'string', 'max:255'],
            'numero_agrement' => [
                'nullable', 'required_if:type_compte,' . Transporteur::TYPE_COMPAGNIE, 'string', 'max:50',
                Rule::unique('transporteurs', 'numero_agrement'),
            ],
        ], [
            'telephone.regex' => $accepterFixe
                ? 'Entrez un numéro sénégalais valide : mobile (70, 75, 76, 77, 78) ou fixe (33).'
                : 'Entrez un numéro mobile sénégalais valide (70, 75, 76, 77 ou 78).',
            'telephone.unique' => 'Ce numéro est déjà utilisé.',
            'accepte_cgu.accepted' => 'Vous devez accepter les conditions générales pour continuer.',
            'nom_compagnie.required_if' => 'Indiquez le nom de votre compagnie.',
            'numero_agrement.required_if' => "Indiquez votre numéro d'agrément.",
            'numero_agrement.unique' => "Ce numéro d'agrément est déjà enregistré.",
        ]);

        $user = DB::transaction(function () use ($donnees) {
            $user = User::create([
                'name' => trim($donnees['prenom'] . ' ' . $donnees['nom']),
                'email' => $donnees['email'],
                'telephone' => $donnees['telephone'],
                'password' => Hash::make($donnees['password']),
            ]);

            if ($donnees['type_compte'] !== 'voyageur') {
                $user->role = User::ROLE_TRANSPORTEUR;
                $user->save();

                Transporteur::create([
                    'user_id' => $user->id,
                    'type' => $donnees['type_compte'],
                    'nom_compagnie' => $donnees['nom_compagnie'] ?? null,
                    'numero_agrement' => $donnees['type_compte'] === Transporteur::TYPE_COMPAGNIE
                        ? $donnees['numero_agrement']
                        : null,
                    'statut' => Transporteur::STATUT_EN_ATTENTE,
                ]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}