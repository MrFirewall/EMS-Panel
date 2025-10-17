<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_id')->constrained()->onDelete('cascade'); // Verknüpfung zum Bürger/Patient
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Verknüpfung zum ausstellenden Arzt (User)
            $table->string('medication'); // Name des Medikaments
            $table->string('dosage');     // Dosierung (z.B. "1-0-1", "500mg")
            $table->text('notes')->nullable(); // Hinweise zur Einnahme
            $table->timestamps(); // Erstellt am / Aktualisiert am
        });
    }

    public function down()
    {
        Schema::dropIfExists('prescriptions');
    }
}
