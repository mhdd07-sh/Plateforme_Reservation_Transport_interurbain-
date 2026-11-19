<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('montant');
            $table->string('moyen_paiement', 30); // wave, orange_money, carte
            $table->string('numero_paiement', 20)->nullable();
            $table->string('statut_paiement', 20)->default('initie'); // initie, reussi, echoue, rembourse
            $table->string('reference_transaction', 60)->nullable()->unique();
            $table->timestamp('date_paiement')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};