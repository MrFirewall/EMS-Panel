<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_module_id')->unique()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('pass_mark')->default(75);
            $table->timestamps();
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->text('question_text');
            $table->enum('type', ['single_choice', 'multiple_choice'])->default('single_choice');
            $table->timestamps();
        });
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->after('id')->unique()->nullable(); // UUID ist hier enthalten
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('score')->nullable();
            $table->enum('status', ['in_progress', 'submitted', 'evaluated'])->default('in_progress');
            $table->json('flags')->nullable();
            $table->timestamps();
        });
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->foreignId('option_id')->constrained()->onDelete('cascade');
            $table->boolean('is_correct_at_time_of_answer');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('exams');
    }
};
