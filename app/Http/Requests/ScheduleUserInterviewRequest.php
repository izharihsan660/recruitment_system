<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleUserInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'interviewer_id' => ['required', 'exists:users,id'],
        ];
    }
}
