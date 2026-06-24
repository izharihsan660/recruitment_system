<?php

namespace App\Http\Requests\Admin;

class StoreGraphApiConfigRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['required', 'string'],
            'calendar_user_email' => ['required', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
