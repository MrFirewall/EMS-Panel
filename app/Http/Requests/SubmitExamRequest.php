<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policy prüft, ob der User diesen Versuch einreichen darf
        return $this->user()->can('submit', $this->route('attempt'));
    }

    public function rules(): array
    {
        return [
            'answers' => 'required|array',
            'answers.*' => 'nullable', // Detailliertere Validierung ist hier zu komplex
        ];
    }
}
