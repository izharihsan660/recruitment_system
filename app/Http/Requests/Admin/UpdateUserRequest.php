<?php

namespace App\Http\Requests\Admin;

use App\Support\Roles;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(Roles::all())],
        ];
    }
}
