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
        // Tabelle für die Prüfungen selbst, verknüpft mit einem Ausbildungsmodul
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_module_id')->unique()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('pass_mark')->default(75); // Benötigte Prozentzahl zum Bestehen
            $table->timestamps();
        });

        // Tabelle für die einzelnen Fragen einer Prüfung
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->text('question_text');
            $table->enum('type', ['single_choice', 'multiple_choice'])->default('single_choice');
            $table->timestamps();
        });

        // Tabelle für die Antwortmöglichkeiten einer Frage
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        // Tabelle, die einen Prüfungsversuch eines Benutzers speichert
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('score')->nullable(); // Erreichte Prozentzahl
            $table->enum('status', ['in_progress', 'submitted', 'evaluated'])->default('in_progress');
            $table->json('flags')->nullable(); // Speichert Zeitstempel bei verdächtigen Aktivitäten
            $table->timestamps();
        });

        // Tabelle, die die gegebene Antwort eines Benutzers für eine Frage speichert
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->foreignId('option_id')->constrained()->onDelete('cascade'); // Die gewählte Antwortmöglichkeit
            $table->boolean('is_correct_at_time_of_answer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('exams');
    }
};
