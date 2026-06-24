<?php

namespace App\Http\Requests;

use App\Models\JobPosting;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', JobPosting::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'recruitment_request_id' => ['required', 'integer', 'exists:recruitment_requests,id'],
            'position_name' => ['sometimes', 'string', 'max:255'],
            'work_location' => ['sometimes', 'string', 'max:255'],
            'job_description' => ['sometimes', 'string'],
            'requirements' => ['required', 'string'],
            'test_required' => ['sometimes', 'boolean'],
            'mcu_required' => ['sometimes', 'boolean'],
            'simper_required' => ['sometimes', 'boolean'],
        ];
    }
}
