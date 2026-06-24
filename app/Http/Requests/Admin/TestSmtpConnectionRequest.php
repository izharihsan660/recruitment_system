<?php

namespace App\Http\Requests\Admin;

class TestSmtpConnectionRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
