<?php

namespace App\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

class RejectEmailIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Roles::Admin, Roles::HrRecruiter, Roles::HrManager]) === true;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string']];
    }
}
