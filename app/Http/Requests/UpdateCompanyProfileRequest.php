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
            'company_name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string'],
            'values' => ['sometimes', 'array'],
            'values.*.title' => ['required', 'string', 'max:255'],
            'values.*.description' => ['required', 'string'],
            'gallery' => ['sometimes', 'array'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = collect($this->input('values', []))
            ->filter(fn (array $value): bool => filled($value['title'] ?? null) || filled($value['description'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'company_name' => $this->input('company_name', ''),
            'about' => $this->input('about', ''),
            'values' => $values,
        ]);
    }
}
