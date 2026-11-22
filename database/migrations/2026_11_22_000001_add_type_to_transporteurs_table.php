<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transporteurs', function (Blueprint $table) {
            // particulier (7 ou 14 places) ou compagnie (flotte, gares)
            $table->string('type', 20)->default('compagnie')->after('user_id');

            // Un particulier n'a pas de numéro d'agrément.
            $table->string('numero_agrement', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transporteurs', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->string('numero_agrement', 50)->nullable(false)->change();
        });
    }
};