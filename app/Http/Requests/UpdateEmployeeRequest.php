<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['phone' => ['nullable', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'status' => ['sometimes', 'in:pending_activation,active,cancelled']];
    }
}
