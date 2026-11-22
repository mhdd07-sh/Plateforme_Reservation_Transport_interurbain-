<?php

namespace App\Http\Requests\Auth;

use App\Support\Telephone;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifiant' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifiant.required' => 'Saisissez votre e-mail ou votre numéro de téléphone.',
            'password.required' => 'Saisissez votre mot de passe.',
        ];
    }

    /**
     * Connexion par e-mail OU par numéro de téléphone.
     * Un compte désactivé (actif = false) ne peut pas se connecter.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifiant = trim((string) $this->input('identifiant'));

        $identifiants = str_contains($identifiant, '@')
            ? ['email' => mb_strtolower($identifiant)]
            : ['telephone' => Telephone::normaliser($identifiant)];

        $identifiants['password'] = $this->input('password');
        $identifiants['actif'] = true;

        if (! Auth::attempt($identifiants, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'identifiant' => 'Identifiants incorrects, ou compte désactivé.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $secondes = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'identifiant' => "Trop de tentatives. Réessayez dans {$secondes} secondes.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('identifiant')) . '|' . $this->ip());
    }
}