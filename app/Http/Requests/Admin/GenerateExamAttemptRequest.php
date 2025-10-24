<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\TrainingModule;

class GenerateExamAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('generateExamLink', \App\Models\ExamAttempt::class);
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'module_id' => [
                'required',
                'exists:training_modules,id',
                // Eigene Regel, um sicherzustellen, dass das Modul eine Prüfung hat
                function ($attribute, $value, $fail) {
                    $module = TrainingModule::find($value);
                    if ($module && !$module->exam) {
                        $fail('Für dieses Modul ist keine Prüfung hinterlegt.');
                    }
                },
            ],
            'evaluation_id' => 'required|exists:evaluations,id',
        ];
    }
}
