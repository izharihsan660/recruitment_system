<?php

namespace App\Http\Requests\Admin;

class UpdateDocusealConfigRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'api_url' => ['sometimes', 'required', 'url', 'max:255'],
            'api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'webhook_secret' => ['sometimes', 'nullable', 'string', 'max:255'],
            'offering_template_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pkwt_template_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
