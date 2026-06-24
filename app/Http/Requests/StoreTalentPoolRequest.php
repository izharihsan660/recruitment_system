<?php

namespace App\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

class StoreTalentPoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Roles::HrRecruiter, Roles::HrManager]) === true;
    }

    public function rules(): array
    {
        return [
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'status' => ['nullable', 'in:active,passive,hot_prospect,on_hold,do_not_contact,hired_elsewhere,archived'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
