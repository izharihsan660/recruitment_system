<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleSimperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['simper_scheduled_at' => ['required', 'date'], 'simper_location' => ['required', 'string', 'max:255']];
    }
}
