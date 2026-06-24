<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreCompanySignerRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'entity_id' => ['required', 'integer', 'exists:entities,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'document_type' => ['required', Rule::in(['offering', 'pkwt'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
