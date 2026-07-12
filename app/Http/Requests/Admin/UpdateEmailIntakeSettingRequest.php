<?php

namespace App\Http\Requests\Admin;

use App\Support\Roles;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailIntakeSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Roles::Admin) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:1000'],
            'mailbox_address' => ['required', 'email', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'sync_interval_minutes' => ['required', 'integer', 'min:10', 'max:1440'],
        ];
    }
}
