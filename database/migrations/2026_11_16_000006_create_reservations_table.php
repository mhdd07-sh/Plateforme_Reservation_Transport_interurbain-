<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique(); // ex. RES-AB12CD34 (utilisée pour le QR code)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trajet_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('nombre_places');
            $table->unsignedInteger('montant_total');
            $table->string('operateur_paiement', 30)->nullable();
            $table->string('statut', 20)->default('en_attente'); // en_attente, confirmee, annulee, expiree
            $table->timestamp('expire_at')->nullable();
            $table->timestamps();

            $table->index(['statut', 'expire_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};