<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateCompanySignerRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'entity_id' => ['sometimes', 'required', 'integer', 'exists:entities,id'],
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'document_type' => ['sometimes', 'required', Rule::in(['offering', 'pkwt'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
