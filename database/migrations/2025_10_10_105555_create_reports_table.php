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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Der Autor des Berichts
            $table->string('title'); // Titel / Einsatzstichwort
            $table->string('patient_name'); // Name des Patienten
            $table->text('incident_description'); // Was ist passiert?
            $table->text('actions_taken'); // Was wurde getan?
            $table->string('location'); // Ort des Geschehens
            $table->timestamps(); // Erstellt am / Geändert am
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
