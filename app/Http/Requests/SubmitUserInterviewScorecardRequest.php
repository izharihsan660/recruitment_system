<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitUserInterviewScorecardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score_technical' => ['required', 'integer', 'between:1,5'],
            'score_experience' => ['required', 'integer', 'between:1,5'],
            'score_problem_solving' => ['required', 'integer', 'between:1,5'],
            'score_team_fit' => ['required', 'integer', 'between:1,5'],
            'recommendation' => ['required', Rule::in(['accepted', 'considered', 'rejected'])],
            'rejection_reason' => ['nullable', 'required_if:recommendation,rejected', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
