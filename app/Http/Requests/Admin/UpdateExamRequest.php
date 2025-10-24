<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('exam'));
    }

    public function rules(): array
    {
        // $examId = $this->route('exam')->id; // Nicht mehr nötig ohne unique-Prüfung auf module_id

        return [
             // 'training_module_id' entfernt
            'title' => 'required|string|max:255',
            'pass_mark' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer|exists:questions,id,exam_id,' . $this->route('exam')->id, // Existenz prüfen, aber nur innerhalb dieser Prüfung
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => ['required', Rule::in(['single_choice', 'multiple_choice', 'text_field'])],
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.id' => 'nullable|integer', // Existenzprüfung hier schwierig, da dynamisch
            'questions.*.options.*.option_text' => 'nullable|string',
            'questions.*.options.*.is_correct' => 'nullable',
            'questions.*.correct_option' => 'nullable|integer',
        ];
    }

    /**
     * Fügt komplexe, konditionale Validierungsregeln hinzu.
     * (Identisch zu StoreExamRequest)
     */
    public function withValidator(Validator $validator): void
    {
         $validator->after(function ($validator) {
            foreach ($this->input('questions', []) as $key => $question) {
                $type = $question['type'] ?? null;
                 if (!$type || !in_array($type, ['single_choice', 'multiple_choice', 'text_field'])) {
                     continue;
                 }

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
                    if (!isset($question['correct_option']) || !is_numeric($question['correct_option'])) {
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
