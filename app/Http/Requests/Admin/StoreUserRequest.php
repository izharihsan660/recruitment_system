<?php

namespace App\Http\Requests\Admin;

use App\Support\Roles;
use Illuminate\Validation\Rule;

class StoreUserRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(Roles::all())],
        ];
    }
}
