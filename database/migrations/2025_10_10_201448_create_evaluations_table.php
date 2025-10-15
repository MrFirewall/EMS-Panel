<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erstellt die 'evaluations' Tabelle zur Speicherung aller Mitarbeiter- und Praktikantenbewertungen.
     */
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            
            // Betroffener Benutzer: Die ID des bewerteten Users. NULL, wenn es ein nicht registrierter Praktikant ist.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Evaluator: Wer die Bewertung erstellt hat.
            $table->foreignId('evaluator_id')->constrained('users')->onDelete('cascade');
            
            // Name des Betroffenen (wird für nicht registrierte Praktikanten manuell gesetzt, sonst vom User-Model)
            $table->string('target_name')->nullable();

            // Typ der Bewertung
            $table->enum('evaluation_type', ['azubi', 'praktikant', 'leitstelle', 'mitarbeiter']);
            
            // Bewerteter Zeitraum
            $table->string('period')->nullable();

            // Die eigentlichen Bewertungsdaten (z.B. Verhalten, Kompetenz etc.) als JSON-Array
            $table->json('json_data');
            
            // Zusätzliche Kommentare
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Löscht die Tabelle beim Rollback der Migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
