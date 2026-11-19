<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporteur_id')->constrained()->cascadeOnDelete();
            $table->string('immatriculation', 20)->unique();
            $table->string('marque', 50);
            $table->unsignedSmallInteger('capacite');
            $table->string('type_vehicule', 20); // tata, minibus, car
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicules');
    }
};