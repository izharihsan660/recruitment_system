<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['employee_id' => ['required', 'string', 'max:255', Rule::unique('employees', 'employee_id')], 'start_date' => ['required', 'date']];
    }
}
