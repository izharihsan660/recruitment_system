<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleMcuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['mcu_scheduled_at' => ['required', 'date'], 'mcu_location' => ['required', 'string', 'max:255']];
    }
}
