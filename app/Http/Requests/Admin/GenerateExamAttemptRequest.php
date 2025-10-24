<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
// use App\Models\TrainingModule; // Nicht mehr benötigt
use App\Models\Exam; // NEU

class GenerateExamAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Prüft, ob der User generell Links generieren darf
        return $this->user()->can('generateExamLink', \App\Models\ExamAttempt::class);
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            // ALT: 'module_id' => [...]
            'exam_id' => 'required|exists:exams,id', // NEU: Prüfung direkt auswählen
            // Evaluation ID ist jetzt optional, falls der Link nicht aus einem Antrag generiert wird
            'evaluation_id' => 'nullable|exists:evaluations,id',
        ];
    }
}
