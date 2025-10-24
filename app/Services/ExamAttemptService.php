<?php

namespace App\Services;

use App\Models\Exam; // Exam statt TrainingModule importieren
use App\Models\User;
use App\Models\ExamAttempt;
// use App\Models\TrainingModule; // Nicht mehr benötigt
use Illuminate\Support\Facades\DB;

class ExamAttemptService
{
    /**
     * Erstellt einen neuen Prüfungsversuch für einen Benutzer für eine spezifische Prüfung.
     */
    public function generateAttempt(User $user, Exam $exam): ExamAttempt // Nimmt jetzt Exam statt TrainingModule
    {
        return ExamAttempt::create([
            'exam_id' => $exam->id, // Direkt die ID der Prüfung verwenden
            'user_id' => $user->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    /**
     * Verarbeitet die Einreichung einer Prüfung und berechnet das Ergebnis.
     */
    public function submitAttempt(ExamAttempt $attempt, array $answers): ExamAttempt
    {
        $correctAnswers = 0;
        $questions = $attempt->exam->questions()->with('options')->get()->keyBy('id');

        DB::transaction(function () use ($answers, $attempt, &$correctAnswers, $questions) {

            // Alte Antworten löschen
            $attempt->answers()->delete();

            foreach ($answers as $questionId => $submittedAnswer) {
                $question = $questions->get($questionId);
                if (!$question) continue;

                switch ($question->type) {
                    case 'single_choice':
                        $option = $question->options->find($submittedAnswer);
                        $isCorrect = $option && $option->is_correct;
                        if ($isCorrect) $correctAnswers++;

                        $attempt->answers()->create([
                            'question_id' => $questionId,
                            'option_id' => $submittedAnswer,
                            'is_correct_at_time_of_answer' => $isCorrect,
                        ]);
                        break;

                    case 'multiple_choice':
                        $submittedAnswerIds = collect(is_array($submittedAnswer) ? $submittedAnswer : []);
                        $correctOptionIds = $question->options->where('is_correct', true)->pluck('id');
                        $isCorrect = $submittedAnswerIds->sort()->values()->all() == $correctOptionIds->sort()->values()->all();

                        if ($isCorrect) $correctAnswers++;

                        foreach ($submittedAnswerIds as $optionId) {
                            $option = $question->options->firstWhere('id', $optionId);
                            $isOptionCorrect = $option && $option->is_correct;

                            $attempt->answers()->create([
                                'question_id' => $questionId,
                                'option_id' => $optionId,
                                'is_correct_at_time_of_answer' => $isOptionCorrect,
                            ]);
                        }
                        break;

                    case 'text_field':
                        $attempt->answers()->create([
                            'question_id' => $questionId,
                            'option_id' => null,
                            'text_answer' => $submittedAnswer,
                            'is_correct_at_time_of_answer' => 0,
                        ]);
                        break;
                }
            }

            // Ergebnis berechnen
            $scorableQuestionsCount = $questions->whereIn('type', ['single_choice', 'multiple_choice'])->count();
            $score = ($scorableQuestionsCount > 0) ? round(($correctAnswers / $scorableQuestionsCount) * 100) : 0;

            $attempt->update([
                'completed_at' => now(),
                'status' => 'submitted',
                'score' => $score,
            ]);
        });

        return $attempt;
    }

    /**
     * Schließt eine Prüfung final ab (setzt Status und Score).
     * Entfernt die Modulstatus-Aktualisierung.
     */
    public function finalizeAttempt(ExamAttempt $attempt, array $validatedData): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $validatedData) {
            $attempt->update([
                'score' => $validatedData['final_score'],
                'status' => 'evaluated',
            ]);

            // --- Modul-Update ENTFERNT ---

            return $attempt;
        });
    }

    /**
     * Setzt einen Prüfungsversuch zurück.
     */
    public function resetAttempt(ExamAttempt $attempt): ExamAttempt
    {
        DB::transaction(function () use ($attempt) {
            $attempt->answers()->delete();
            $attempt->update([
                'status' => 'in_progress',
                'completed_at' => null,
                'score' => null,
                'flags' => null,
                'started_at' => now(), // Startzeit zurücksetzen
            ]);
        });

        return $attempt;
    }

    /**
     * Löscht einen Prüfungsversuch und alle zugehörigen Antworten.
     */
    public function deleteAttempt(ExamAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            // Erst die Antworten löschen
            $attempt->answers()->delete();
            // Dann den Versuch selbst löschen
            $attempt->delete();
        });
    }
}
