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
    Schema::table('users', function (Blueprint $table) {
        $table->string('personal_number')->nullable()->after('rank');
        $table->string('employee_id')->nullable()->after('personal_number');
        $table->string('email')->nullable()->after('employee_id');
        $table->date('birthday')->nullable()->after('email');
        $table->string('discord_name')->nullable()->after('birthday');
        $table->string('forum_name')->nullable()->after('discord_name');
        $table->text('special_functions')->nullable()->after('forum_name');
        $table->string('second_faction')->nullable()->after('special_functions');
        $table->date('hire_date')->nullable()->after('second_faction');
        $table->timestamp('last_edited_at')->nullable()->after('updated_at');
        $table->string('last_edited_by')->nullable()->after('last_edited_at');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
