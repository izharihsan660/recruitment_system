<?php

namespace App\Http\Requests\Admin;

class StoreDocusealConfigRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'api_url' => ['required', 'url', 'max:255'],
            'api_key' => ['required', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'offering_template_id' => ['nullable', 'string', 'max:255'],
            'pkwt_template_id' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
