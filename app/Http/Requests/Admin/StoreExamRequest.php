<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Exam::class);
    }

    public function rules(): array
    {
        return [
            'training_module_id' => 'required|exists:training_modules,id|unique:exams,training_module_id',
            'title' => 'required|string|max:255',
            'pass_mark' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer', // 'exists' ist hier riskant, da wir 'null' für neue wollen
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => 'required|in:single_choice,multiple_choice,text_field',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.id' => 'nullable',
            'questions.*.options.*.option_text' => 'nullable',
            'questions.*.options.*.is_correct' => 'nullable',
            'questions.*.correct_option' => 'nullable',
        ];
    }

    /**
     * Fügt komplexe, konditionale Validierungsregeln hinzu.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('questions', []) as $key => $question) {
                $type = $question['type'] ?? 'single_choice';
                if ($type === 'text_field') continue;

                if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
                    $validator->errors()->add("questions.{$key}.options", "Für eine Auswahlfrage werden mindestens 2 Antwortmöglichkeiten benötigt.");
                    continue;
                }

                foreach($question['options'] as $optKey => $option) {
                    if(empty($option['option_text'])) {
                        $validator->errors()->add("questions.{$key}.options.{$optKey}.option_text", "Der Antworttext darf nicht leer sein.");
                    }
                }

                if ($type === 'single_choice') {
                    if (!isset($question['correct_option'])) {
                        $validator->errors()->add("questions.{$key}.correct_option", "Für eine Einzelantwort-Frage muss eine korrekte Antwort markiert sein.");
                    }
                } elseif ($type === 'multiple_choice') {
                    $hasCorrect = collect($question['options'])->contains(fn ($opt) => isset($opt['is_correct']) && $opt['is_correct'] == '1');
                    if (!$hasCorrect) {
                        $validator->errors()->add("questions.{$key}.options", "Für eine Mehrfachantwort-Frage muss mindestens eine korrekte Antwort markiert sein.");
                    }
                }
            }
        });
    }
}
