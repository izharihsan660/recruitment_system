<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitProbationOutcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['outcome' => ['required', Rule::in(['permanent', 'extended', 'terminated'])], 'extended_until' => ['nullable', 'required_if:outcome,extended', 'date']];
    }
}
