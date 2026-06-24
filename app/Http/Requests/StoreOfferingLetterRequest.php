<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferingLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'contract_duration' => ['nullable', 'string', 'max:255'],
            'salary_gross' => ['nullable', 'integer', 'min:0'],
            'salary_nett' => ['nullable', 'integer', 'min:0'],
            'allowances' => ['nullable', 'array'],
            'allowances.*' => ['nullable', 'integer', 'min:0'],
            'expiry_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
