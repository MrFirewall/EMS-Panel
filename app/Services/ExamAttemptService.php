<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\User;
use App\Models\ExamAttempt;
use App\Models\TrainingModule;
use Illuminate\Support\Facades\DB;

class ExamAttemptService
{
    /**
     * Erstellt einen neuen Prüfungsversuch für einen Benutzer.
     */
    public function generateAttempt(User $user, TrainingModule $module): ExamAttempt
    {
        if (!$module->exam) {
            // Dieser Fall sollte idealerweise bereits im Controller/Request abgefangen werden
            throw new \Exception('Für dieses Modul ist keine Prüfung hinterlegt.');
        }

        return ExamAttempt::create([
            'exam_id' => $module->exam->id,
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
            
            // Alte Antworten löschen, falls dies ein erneuter Versuch ist (sollte nicht passieren, aber sicher ist sicher)
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
     * Schließt eine Prüfung final ab und aktualisiert den Modulstatus des Users.
     */
    public function finalizeAttempt(ExamAttempt $attempt, array $validatedData): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $validatedData) {
            $attempt->update([
                'score' => $validatedData['final_score'],
                'status' => 'evaluated',
            ]);

            $module = $attempt->exam->trainingModule;
            if ($module) {
                $module->users()->updateExistingPivot($attempt->user_id, [
                    'status' => $validatedData['status_result'],
                    'completed_at' => now()->toDateString(),
                    'notes' => 'Abgeschlossen durch Prüfung: ' . $attempt->exam->title,
                ]);
            }
            
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
     * VORSICHT: Dies ist ein destruktiver Vorgang.
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
