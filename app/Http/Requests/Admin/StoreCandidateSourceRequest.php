<?php

namespace App\Http\Requests\Admin;

class StoreCandidateSourceRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:candidate_sources,name'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
