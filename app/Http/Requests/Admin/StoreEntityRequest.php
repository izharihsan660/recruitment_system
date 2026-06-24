<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreEntityRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:50', 'unique:entities,short_name'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
