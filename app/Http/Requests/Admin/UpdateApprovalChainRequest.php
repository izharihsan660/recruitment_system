<?php

namespace App\Http\Requests\Admin;

use App\Support\Roles;
use Illuminate\Validation\Rule;

class UpdateApprovalChainRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $approvalChain = $this->route('approval_chain');
        $departmentId = $this->integer('department_id') ?: $approvalChain?->department_id;

        return [
            'department_id' => ['sometimes', 'required', 'integer', 'exists:departments,id'],
            'level' => ['sometimes', 'required', 'integer', 'min:1', 'max:3', Rule::unique('approval_chains')->where('department_id', $departmentId)->ignore($approvalChain)],
            'type' => ['sometimes', 'required', Rule::in(['user', 'role'])],
            'approver_user_id' => ['nullable', 'required_if:type,user', 'prohibited_if:type,role', 'integer', 'exists:users,id'],
            'approver_role' => ['nullable', 'required_if:type,role', 'prohibited_if:type,user', Rule::in(Roles::all())],
        ];
    }
}
