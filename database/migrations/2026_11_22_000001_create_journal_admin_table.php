<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_admin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // l'administrateur
            $table->string('action', 50);
            $table->string('cible_type', 50)->nullable();
            $table->unsignedBigInteger('cible_id')->nullable();
            $table->string('libelle');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cible_type', 'cible_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_admin');
    }
};