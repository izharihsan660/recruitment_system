<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreApprovalChainRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id', Rule::unique('approval_chains', 'approver_user_id')->where('department_id', $this->integer('department_id'))],
        ];
    }
}
