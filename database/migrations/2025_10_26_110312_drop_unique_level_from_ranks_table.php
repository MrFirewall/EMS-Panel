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
        Schema::table('ranks', function (Blueprint $table) {
            // Laravel hat den Index automatisch 'ranks_level_unique' genannt.
            $table->dropUnique('ranks_level_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            // Falls du die Migration rückgängig machst, füge die Regel wieder hinzu
            $table->unique('level');
        });
    }
};