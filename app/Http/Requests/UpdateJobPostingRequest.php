<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('job_posting')) ?? false;
    }

    public function rules(): array
    {
        return [
            'position_name' => ['sometimes', 'string', 'max:255'],
            'work_location' => ['sometimes', 'string', 'max:255'],
            'job_description' => ['sometimes', 'string'],
            'requirements' => ['sometimes', 'string'],
            'test_required' => ['sometimes', 'boolean'],
            'mcu_required' => ['sometimes', 'boolean'],
            'simper_required' => ['sometimes', 'boolean'],
        ];
    }
}
