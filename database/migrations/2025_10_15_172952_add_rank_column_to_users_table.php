<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Fügt die neue Spalte 'rank' nach der Spalte 'status' hinzu.
            // Sie kann leer sein (nullable).
            $table->string('rank')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Entfernt die Spalte wieder, falls die Migration zurückgerollt wird.
            $table->dropColumn('rank');
        });
    }
};
