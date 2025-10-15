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
        Schema::create('service_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Zu wem gehört der Eintrag?
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade'); // Wer hat den Eintrag erstellt?
            $table->string('type'); // Art des Eintrags (z.B. Lob, Verwarnung, Fortbildung)
            $table->text('content'); // Der eigentliche Text des Vermerks
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_records');
    }
};
