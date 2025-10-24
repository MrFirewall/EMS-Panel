<?php

namespace App\Services;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ExamValidationException;

class ExamService
{
    /**
     * Erstellt eine neue Prüfungsvorlage inklusive Fragen und Optionen.
     */
    public function createExam(array $validatedData): Exam
    {
        return DB::transaction(function () use ($validatedData) {
            $exam = Exam::create([
                'training_module_id' => $validatedData['training_module_id'],
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'pass_mark' => $validatedData['pass_mark'],
            ]);

            $this->syncQuestions($exam, $validatedData['questions']);

            return $exam;
        });
    }

    /**
     * Aktualisiert eine bestehende Prüfungsvorlage.
     */
    public function updateExam(Exam $exam, array $validatedData): Exam
    {
        return DB::transaction(function () use ($exam, $validatedData) {
            $exam->update([
                'training_module_id' => $validatedData['training_module_id'],
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'pass_mark' => $validatedData['pass_mark'],
            ]);

            $this->syncQuestions($exam, $validatedData['questions']);

            return $exam;
        });
    }

    /**
     * Synchronisiert die Fragen und Optionen für eine Prüfung.
     * Erstellt, aktualisiert oder löscht Einträge bei Bedarf.
     */
    private function syncQuestions(Exam $exam, array $questionsData): void
    {
        $submittedQuestionIds = [];

        foreach ($questionsData as $questionData) {
            $question = $exam->questions()->updateOrCreate(
                ['id' => $questionData['id'] ?? null],
                [
                    'question_text' => $questionData['question_text'],
                    'type' => $questionData['type']
                ]
            );
            $submittedQuestionIds[] = $question->id;

            if ($questionData['type'] !== 'text_field' && isset($questionData['options'])) {
                $submittedOptionIds = [];
                foreach ($questionData['options'] as $oIndex => $optionData) {
                    $isCorrect = false;
                    if ($questionData['type'] === 'single_choice') {
                        $isCorrect = (isset($questionData['correct_option']) && $oIndex == $questionData['correct_option']);
                    } elseif ($questionData['type'] === 'multiple_choice') {
                        $isCorrect = isset($optionData['is_correct']) && $optionData['is_correct'] == '1';
                    }

                    $option = $question->options()->updateOrCreate(
                        ['id' => $optionData['id'] ?? null],
                        [
                            'option_text' => $optionData['option_text'],
                            'is_correct' => $isCorrect
                        ]
                    );
                    $submittedOptionIds[] = $option->id;
                }
                // Lösche veraltete Optionen für diese Frage
                $question->options()->whereNotIn('id', $submittedOptionIds)->delete();
            } else {
                // Lösche alle Optionen, falls Typ auf 'text_field' geändert wurde
                $question->options()->delete();
            }
        }
        // Lösche veraltete Fragen für diese Prüfung
        $exam->questions()->whereNotIn('id', $submittedQuestionIds)->delete();
    }
}
