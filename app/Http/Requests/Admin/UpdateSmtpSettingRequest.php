<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateSmtpSettingRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'host' => ['sometimes', 'required', 'string', 'max:255'],
            'port' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'username' => ['sometimes', 'required', 'string', 'max:255'],
            'password' => ['sometimes', 'required', 'string'],
            'encryption' => ['nullable', 'string', Rule::in(['smtp', 'smtps', 'tls', 'ssl'])],
            'from_address' => ['sometimes', 'required', 'email', 'max:255'],
            'from_name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
