<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreSmtpSettingRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'encryption' => ['nullable', 'string', Rule::in(['smtp', 'smtps', 'tls', 'ssl'])],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
