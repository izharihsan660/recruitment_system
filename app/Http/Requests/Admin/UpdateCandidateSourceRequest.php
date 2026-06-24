<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateCandidateSourceRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('candidate_sources', 'name')->ignore($this->route('candidateSource'))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
