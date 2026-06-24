<?php

namespace App\Http\Requests\Admin;

use App\Support\Roles;
use Illuminate\Validation\Rule;

class StoreApprovalChainRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'level' => ['required', 'integer', 'min:1', 'max:3', Rule::unique('approval_chains')->where('department_id', $this->integer('department_id'))],
            'type' => ['required', Rule::in(['user', 'role'])],
            'approver_user_id' => ['nullable', 'required_if:type,user', 'prohibited_if:type,role', 'integer', 'exists:users,id'],
            'approver_role' => ['nullable', 'required_if:type,role', 'prohibited_if:type,user', Rule::in(Roles::all())],
        ];
    }
}
