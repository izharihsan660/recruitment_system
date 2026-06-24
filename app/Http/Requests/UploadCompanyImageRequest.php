<?php

namespace App\Http\Requests;

use App\Models\CompanyProfile;
use Illuminate\Foundation\Http\FormRequest;

class UploadCompanyImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', CompanyProfile::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
