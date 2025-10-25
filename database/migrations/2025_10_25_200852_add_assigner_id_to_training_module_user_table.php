<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_module_user', function (Blueprint $table) {
            // 1. 'status' entfernen (falls die Spalte existiert)
            if (Schema::hasColumn('training_module_user', 'status')) {
                $table->dropColumn('status');
            }

            // 2. 'assigned_by_user_id' hinzufügen
            $table->foreignId('assigned_by_user_id')
                  ->nullable() // Erlaubt Selbstanmeldungen oder Zuweisungen durch "System"
                  ->constrained('users')
                  ->onDelete('set null') // Löscht den Zuweiser-User nicht, wenn er gelöscht wird
                  ->after('user_id'); // Positionierung nach user_id
        });
    }

    public function down(): void
    {
        Schema::table('training_module_user', function (Blueprint $table) {
            // 1. 'assigned_by_user_id' entfernen
            $table->dropForeign(['assigned_by_user_id']);
            $table->dropColumn('assigned_by_user_id');

            // 2. 'status' wieder hinzufügen (falls nötig für den Rollback)
            $table->string('status', 30)->default('angemeldet');
        });
    }
};
