<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitBackgroundCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ktp_verified' => ['sometimes', 'boolean'],
            'ijazah_verified' => ['sometimes', 'boolean'],
            'certificate_verified' => ['sometimes', 'boolean'],
            'reference_verified' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'decision' => ['required', Rule::in(['clear', 'issue', 'failed'])],
            'rejection_reason' => ['nullable', 'required_if:decision,failed', 'string'],
        ];
    }
}
