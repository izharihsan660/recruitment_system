<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScreeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'education_match' => ['required', 'boolean'],
            'experience_match' => ['required', 'boolean'],
            'document_complete' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
            'decision' => ['required', Rule::in(['passed', 'failed', 'pending_info'])],
            'rejection_reason' => ['nullable', 'required_if:decision,failed', 'string'],
        ];
    }
}
