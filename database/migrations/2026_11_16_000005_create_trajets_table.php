<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trajets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporteur_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicule_id')->constrained()->restrictOnDelete();
            $table->string('ville_depart', 80);
            $table->string('ville_arrivee', 80);
            $table->date('date_depart');
            $table->time('heure_depart');
            $table->unsignedInteger('prix_unitaire'); // en FCFA (pas de centimes)
            $table->string('statut', 20)->default('brouillon'); // brouillon, publie, annule, termine
            $table->timestamps();

            $table->index(['ville_depart', 'ville_arrivee', 'date_depart']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trajets');
    }
};