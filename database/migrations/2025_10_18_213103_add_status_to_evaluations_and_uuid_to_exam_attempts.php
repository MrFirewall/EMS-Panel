<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('evaluation_type'); // pending, processed
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};