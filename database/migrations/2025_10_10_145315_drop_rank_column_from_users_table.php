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
            // Wir entfernen die Spalte, da sie nicht mehr benötigt wird.
            $table->dropColumn('rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Falls etwas schiefgeht, können wir die Spalte wiederherstellen.
            $table->string('rank')->nullable()->after('avatar');
        });
    }
};
