<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitHrInterviewScorecardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score_communication' => ['required', 'integer', 'between:1,5'],
            'score_personality' => ['required', 'integer', 'between:1,5'],
            'score_motivation' => ['required', 'integer', 'between:1,5'],
            'score_attitude' => ['required', 'integer', 'between:1,5'],
            'score_culture_fit' => ['required', 'integer', 'between:1,5'],
            'strengths' => ['nullable', 'string'],
            'weaknesses' => ['nullable', 'string'],
            'salary_expectation' => ['nullable', 'integer', 'min:0'],
            'recommendation' => ['required', Rule::in(['recommended', 'considered', 'not_recommended'])],
            'notes' => ['nullable', 'required_if:recommendation,not_recommended', 'string'],
        ];
    }
}
