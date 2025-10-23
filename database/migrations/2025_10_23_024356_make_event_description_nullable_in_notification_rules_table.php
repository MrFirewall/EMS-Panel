<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            // Ändert die Spalte, um NULL-Werte zuzulassen
            $table->text('event_description')->nullable()->change(); 
        });
    }

    public function down(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            // Setzt die Spalte zurück auf NOT NULL
            $table->string('event_description')->nullable(false)->change();
        });
    }
};