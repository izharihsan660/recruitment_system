<?php

namespace App\Http\Requests;

use App\Models\CompanyProfile;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', CompanyProfile::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'about' => ['required', 'string'],
            'values' => ['required', 'array'],
            'values.*.title' => ['required', 'string', 'max:255'],
            'values.*.description' => ['required', 'string'],
            'gallery' => ['sometimes', 'array'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
        ];
    }
}
