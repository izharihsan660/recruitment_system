<?php

namespace App\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

class RejectPipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Roles::Admin, Roles::HrRecruiter, Roles::HrManager]) === true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string'],
            'skip_talent_pool' => ['sometimes', 'boolean'],
        ];
    }
}
