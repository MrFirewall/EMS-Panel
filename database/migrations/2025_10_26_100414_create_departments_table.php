<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // z.B. 'Rechtsabteilung'
            
            // Annahme: Die Leitungsrolle ist eine Spatie-Rolle (gespeichert als Name)
            $table->string('leitung_role_name'); 
            
            // Wir speichern das *Level* des Rangs, nicht die ID
            $table->integer('min_rank_level_to_assign_leitung'); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
