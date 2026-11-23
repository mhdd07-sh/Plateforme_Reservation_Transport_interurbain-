<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicules', function (Blueprint $table) {
            $table->string('statut', 20)->default('actif')->after('type_vehicule'); // actif, en_maintenance
            $table->text('note_maintenance')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('vehicules', function (Blueprint $table) {
            $table->dropColumn(['statut', 'note_maintenance']);
        });
    }
};