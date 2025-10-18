<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_modules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // z.B. "BLS Zertifizierung"
            $table->text('description')->nullable(); // Was lernt man in diesem Modul?
            $table->string('category')->nullable(); // z.B. "Medizinisch", "Taktisch", "Führung"
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('training_modules'); }
};