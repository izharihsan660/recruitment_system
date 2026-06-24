<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'entity_id' => ['required', 'integer', 'exists:entities,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('departments')->where('entity_id', $this->integer('entity_id'))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
