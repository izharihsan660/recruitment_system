<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitSimperResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['result_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 'status' => ['required', Rule::in(['passed', 'failed'])], 'notes' => ['nullable', 'string'], 'rejection_reason' => ['nullable', 'required_if:status,failed', 'string']];
    }
}
