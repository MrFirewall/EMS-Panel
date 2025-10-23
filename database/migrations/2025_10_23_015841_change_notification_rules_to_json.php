<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            // 1. ZUERST den problematischen Index löschen.
            // Der Indexname ist im CREATE TABLE-Statement zu sehen.
            $table->dropIndex('notification_rules_controller_action_is_active_index');
        });

        Schema::table('notification_rules', function (Blueprint $table) {
            // 2. JETZT den Spaltentyp ändern. Wir verwenden TEXT anstelle von JSON, 
            // da Sie bereits den SQL-Fehler beim Ändern des JSON-Typs hatten
            // (und TEXT robuster für die Speicherung langer Arrays ist).
            $table->text('controller_action')->change();
            $table->text('target_identifier')->change();
        });

        Schema::table('notification_rules', function (Blueprint $table) {
            // 3. Optional: Wenn Sie den Index auf 'is_active' beibehalten möchten, 
            // erstellen Sie ihn ohne 'controller_action' neu.
            $table->index('is_active');
            
            // Sie müssen den Index 'notification_rules_controller_action_is_active_index'
            // NICHT neu erstellen, da er für ein langes Array nicht sinnvoll ist.
        });
    }

    public function down(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            // Index 'is_active' entfernen
            $table->dropIndex(['is_active']); 
            
            // Auf VARCHAR zurücksetzen (nimmt an, dies war der Originaltyp)
            $table->string('controller_action', 255)->change(); 
            
            // Index neu erstellen
            $table->index(['controller_action', 'is_active'], 'notification_rules_controller_action_is_active_index');
        });
    }
};