<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            // Wähle entweder JSON (bevorzugt) oder TEXT
            $table->json('controller_action')->change(); 
            // ODER: $table->text('controller_action')->change();
        });
    }

    public function down(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            // Rückgängig machen (setzt auf VARCHAR(255) zurück, wenn es das Original war)
            $table->string('controller_action', 255)->change(); 
        });
    }
};