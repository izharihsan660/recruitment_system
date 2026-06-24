<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $department = $this->route('department');
        $entityId = $this->integer('entity_id') ?: $department?->entity_id;

        return [
            'entity_id' => ['sometimes', 'required', 'integer', 'exists:entities,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('departments')->where('entity_id', $entityId)->ignore($department)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
