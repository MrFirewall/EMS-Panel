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
        Schema::table('vacations', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacations', function (Blueprint $table) {
            // Hinweis: Dies würde fehlschlagen, wenn NULL-Werte existieren!
            // Für eine einfache App kann man es aber oft weglassen.
            // Besser: Spalte in der down() Methode wieder auf NOT NULL setzen, wenn alle NULL-Werte entfernt wurden.
        });
    }
};
