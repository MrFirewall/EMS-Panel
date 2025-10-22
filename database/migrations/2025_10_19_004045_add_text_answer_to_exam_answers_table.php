<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            // Add a column for text answers
            $table->text('text_answer')->nullable()->after('option_id');
            // Make 'option_id' nullable, as text answers won't have one
            $table->unsignedBigInteger('option_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropColumn('text_answer');
            $table->unsignedBigInteger('option_id')->nullable(false)->change();
        });
    }
};