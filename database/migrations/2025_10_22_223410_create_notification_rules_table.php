<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('event_description'); // Kurze Beschreibung (z.B. "Antrag eingereicht")
            $table->string('controller_action'); // Eindeutig (z.B. 'EvaluationController@store')
            $table->string('target_type'); // 'role', 'permission', 'user'
            $table->string('target_identifier'); // Rollenname, Berechtigungsname oder User-ID
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['controller_action', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
