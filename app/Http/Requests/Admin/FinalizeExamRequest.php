<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policy prüft, ob der Admin diesen Versuch bewerten darf
        return $this->user()->can('setEvaluated', $this->route('attempt'));
    }

    public function rules(): array
    {
        return [
            'final_score' => 'required|integer|min:0|max:100',
            // 'status_result' entfernt
        ];
    }
}
