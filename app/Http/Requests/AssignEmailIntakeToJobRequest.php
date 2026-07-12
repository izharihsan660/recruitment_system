<?php

namespace App\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

class AssignEmailIntakeToJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Roles::Admin, Roles::HrRecruiter, Roles::HrManager]) === true;
    }

    public function rules(): array
    {
        return [
            'job_posting_id' => ['required', 'integer', 'exists:job_postings,id'],
            'consent' => ['required', 'boolean', 'accepted'],
        ];
    }
}
