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
        Schema::table('notification_rules', function (Blueprint $table) {
            // Ändere Spalten zu JSON
            $table->json('controller_action')->nullable()->change();
            $table->json('target_identifier')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            // Mache die Änderung rückgängig (konvertiert JSON zu String)
            $table->string('controller_action')->nullable()->change();
            $table->string('target_identifier')->nullable()->change();
        });
    }
};