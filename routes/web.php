<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// --- Pages publiques -------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');

// --- Redirection après connexion selon le rôle -----------------------------
Route::get('/dashboard', function () {
    $user = auth()->user();

    return match (true) {
        $user->estAdmin() => redirect()->route('admin.dashboard'),
        $user->estTransporteur() => redirect()->route('transporteur.dashboard'),
        default => view('dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/voyageur.php';
require __DIR__.'/transporteur.php';
require __DIR__.'/admin.php';