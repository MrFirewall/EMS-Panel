<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Wir verwenden die Policy-Methode 'setEvaluated'
        return $this->user()->can('setEvaluated', $this->route('attempt'));
    }

    public function rules(): array
    {
        return [
            'final_score' => 'required|integer|min:0|max:100',
            'status_result' => 'required|in:bestanden,nicht_bestanden',
        ];
    }
}
