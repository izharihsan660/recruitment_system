<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCandidateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('candidate') !== null;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
