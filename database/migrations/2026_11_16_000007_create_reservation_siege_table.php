<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invariant : une ligne existe ici SI ET SEULEMENT SI le siège est retenu
     * (réservation en_attente ou confirmee) pour ce trajet.
     * Annulation ou expiration => les lignes sont supprimées => siège libre.
     */
    public function up(): void
    {
        Schema::create('reservation_siege', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('siege_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trajet_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Un siège ne peut être retenu qu'une seule fois par trajet (anti double réservation).
            $table->unique(['trajet_id', 'siege_id']);
            $table->unique(['reservation_id', 'siege_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_siege');
    }
};