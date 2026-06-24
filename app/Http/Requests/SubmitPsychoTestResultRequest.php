<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitPsychoTestResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['passed', 'failed'])],
            'notes' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'required_if:decision,failed', 'string'],
        ];
    }
}
