<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('annulee_le')->nullable()->after('expire_at');
            $table->string('motif_annulation')->nullable()->after('annulee_le');
        });

        Schema::table('trajets', function (Blueprint $table) {
            $table->string('motif_annulation')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['annulee_le', 'motif_annulation']);
        });

        Schema::table('trajets', function (Blueprint $table) {
            $table->dropColumn('motif_annulation');
        });
    }
};