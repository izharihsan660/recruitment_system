<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateEntityRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'short_name' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('entities', 'short_name')->ignore($this->route('entity'))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
