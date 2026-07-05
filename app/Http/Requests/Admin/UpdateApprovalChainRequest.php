<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateApprovalChainRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $approvalChain = $this->route('approval_chain');
        $departmentId = $this->integer('department_id') ?: $approvalChain?->department_id;

        return [
            'department_id' => ['sometimes', 'required', 'integer', 'exists:departments,id'],
            'approver_user_id' => ['required', 'integer', 'exists:users,id', Rule::unique('approval_chains', 'approver_user_id')->where('department_id', $departmentId)->ignore($approvalChain)],
        ];
    }
}
