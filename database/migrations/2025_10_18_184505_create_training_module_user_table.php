<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_module_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_module_id')->constrained()->onDelete('cascade');
            
            // Der Status der Qualifikation
            $table->enum('status', ['angemeldet', 'bestanden', 'nicht_bestanden', 'in_ausbildung'])
                  ->default('angemeldet');
            
            $table->date('completed_at')->nullable(); // Datum des Abschlusses
            $table->text('notes')->nullable(); // z.B. "Prüfer: Dr. Mustermann"
            
            $table->timestamps();
            $table->unique(['user_id', 'training_module_id']); // Ein User kann ein Modul nur einmal haben
        });
    }
    public function down(): void { Schema::dropIfExists('training_module_user'); }
};