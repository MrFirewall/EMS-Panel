<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // 'after' ist optional, aber ordentlich
            $table->foreignId('rank_id')->nullable()->after('id')
                ->constrained('ranks') // Verknüpft mit der 'id' Spalte der 'ranks' Tabelle
                ->onDelete('set null'); // Wenn ein Rang gelöscht wird, wird die ID beim User auf null gesetzt
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['rank_id']);
            $table->dropColumn('rank_id');
        });
    }
};
