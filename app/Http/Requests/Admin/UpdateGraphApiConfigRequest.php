<?php

namespace App\Http\Requests\Admin;

class UpdateGraphApiConfigRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['sometimes', 'required', 'string', 'max:255'],
            'client_id' => ['sometimes', 'required', 'string', 'max:255'],
            'client_secret' => ['sometimes', 'required', 'string'],
            'calendar_user_email' => ['sometimes', 'required', 'email', 'max:255'],
            'recruitment_mailbox' => ['sometimes', 'nullable', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
