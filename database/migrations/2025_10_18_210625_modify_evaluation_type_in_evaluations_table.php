<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Ändert die Spalte, um längere Werte zu erlauben
            $table->string('evaluation_type', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Optional: Macht die Änderung rückgängig, falls nötig
            // Achtung: Dies kann fehlschlagen, wenn bereits lange Werte existieren.
        });
    }
};