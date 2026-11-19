<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transporteurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nom_compagnie')->nullable();
            $table->string('numero_agrement', 50)->unique();
            $table->string('statut', 20)->default('en_attente'); // en_attente, valide, suspendu
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transporteurs');
    }
};