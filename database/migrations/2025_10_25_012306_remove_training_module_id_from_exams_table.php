<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Zuerst den Foreign Key löschen (Name könnte abweichen!)
            $table->dropForeign(['training_module_id']);
            // Dann die Spalte löschen
            $table->dropColumn('training_module_id');
        });
    }
    
    public function down(): void // Für Rollback
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedBigInteger('training_module_id')->nullable()->after('id'); // Wieder hinzufügen
            $table->foreign('training_module_id')->references('id')->on('training_modules')->onDelete('set null'); // Constraint wiederherstellen
        });
    }
}
