<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('candidate') !== null;
    }

    public function rules(): array
    {
        return [
            'consent' => ['required', 'boolean', Rule::in([true, 1, '1'])],
        ];
    }
}
