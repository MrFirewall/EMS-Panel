<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule; // Rule importieren

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Exam::class);
    }

    public function rules(): array
    {
        return [
            // 'training_module_id' entfernt
            'title' => 'required|string|max:255',
            'pass_mark' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer',
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => ['required', Rule::in(['single_choice', 'multiple_choice', 'text_field'])],
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.id' => 'nullable|integer',
            'questions.*.options.*.option_text' => 'nullable|string', // String validieren
            'questions.*.options.*.is_correct' => 'nullable', // Wird im Controller/Service behandelt
            'questions.*.correct_option' => 'nullable|integer', // Index muss eine Zahl sein
        ];
    }

    /**
     * Fügt komplexe, konditionale Validierungsregeln hinzu.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('questions', []) as $key => $question) {
                $type = $question['type'] ?? null; // Typ holen
                 if (!$type || !in_array($type, ['single_choice', 'multiple_choice', 'text_field'])) {
                     continue; // Überspringen, wenn Typ ungültig ist (wird bereits von Basisregel abgefangen)
                 }

                if ($type === 'text_field') continue; // Keine Optionsprüfung für Textfelder

                // Prüfen, ob Optionen vorhanden und mindestens 2
                if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
                    $validator->errors()->add("questions.{$key}.options", "Für eine Auswahlfrage werden mindestens 2 Antwortmöglichkeiten benötigt.");
                    continue; // Weitere Optionsprüfungen überspringen
                }

                // Prüfen, ob Optionstexte leer sind
                foreach($question['options'] as $optKey => $option) {
                    if(empty($option['option_text'])) {
                        $validator->errors()->add("questions.{$key}.options.{$optKey}.option_text", "Der Antworttext darf nicht leer sein.");
                    }
                }

                // Spezifische Prüfungen für Single/Multiple Choice
                if ($type === 'single_choice') {
                    if (!isset($question['correct_option']) || !is_numeric($question['correct_option'])) { // Prüfen, ob gesetzt und numerisch
                        $validator->errors()->add("questions.{$key}.correct_option", "Für eine Einzelantwort-Frage muss eine korrekte Antwort markiert sein.");
                    }
                } elseif ($type === 'multiple_choice') {
                    // Prüfen, ob mindestens eine Checkbox ausgewählt wurde
                    $hasCorrect = collect($question['options'])->contains(fn ($opt) => isset($opt['is_correct']) && $opt['is_correct'] == '1');
                    if (!$hasCorrect) {
                        $validator->errors()->add("questions.{$key}.options", "Für eine Mehrfachantwort-Frage muss mindestens eine korrekte Antwort markiert sein.");
                    }
                }
            }
        });
    }
}
