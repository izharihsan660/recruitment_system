<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitProbationEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['milestone' => ['required', Rule::in(['day30', 'day60', 'day90', 'extended'])], 'performance_notes' => ['required', 'string'], 'recommendation' => ['required', Rule::in(['permanent', 'extended', 'terminated'])]];
    }
}
