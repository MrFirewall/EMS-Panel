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
        Schema::table('questions', function (Blueprint $table) {
            // Ändert die ENUM-Spalte, um den neuen Typ 'text_field' zu erlauben
            $table->enum('type', ['single_choice', 'multiple_choice', 'text_field'])->default('single_choice')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Setzt die Spalte auf den alten Zustand zurück, falls nötig
            $table->enum('type', ['single_choice', 'multiple_choice'])->default('single_choice')->change();
        });
    }
};