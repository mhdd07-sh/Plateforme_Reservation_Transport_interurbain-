<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RechercheTrajetController;
use Illuminate\Support\Facades\Route;

// --- Pages publiques -------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/trajets', [RechercheTrajetController::class, 'index'])->name('trajets.index');
Route::get('/trajets/{trajet}', [RechercheTrajetController::class, 'show'])
    ->whereNumber('trajet')
    ->name('trajets.show');

// --- Redirection après connexion selon le rôle -----------------------------
Route::get('/dashboard', function () {
    $user = auth()->user();

    return match (true) {
        $user->estAdmin() => redirect()->route('admin.dashboard'),
        $user->estTransporteur() => redirect()->route('transporteur.dashboard'),
        default => redirect()->route('voyageur.dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Aperçu des e-mails dans le navigateur (développement uniquement).
// Aperçu des e-mails dans le navigateur (développement uniquement).
if (app()->environment('local')) {
    Route::get('/apercu-mail/{type}', function (string $type) {
        $user = auth()->user() ?? new \App\Models\User(['name' => 'Fatou Sow', 'email' => 'fatou.sow@exemple.sn']);
        $user->id ??= 1;

        return match ($type) {
            'verification' => (new \Illuminate\Auth\Notifications\VerifyEmail)->toMail($user),
            'mot-de-passe' => (new \Illuminate\Auth\Notifications\ResetPassword('jeton-exemple'))->toMail($user),
            default => abort(404),
        };
    });
}

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/voyageur.php';
require __DIR__.'/transporteur.php';
require __DIR__.'/admin.php';