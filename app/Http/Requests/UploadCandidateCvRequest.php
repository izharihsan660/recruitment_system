<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCandidateCvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('candidate') !== null;
    }

    public function rules(): array
    {
        return [
            'cv' => ['required', 'file', 'mimes:pdf', 'max:2048'],
        ];
    }
}
