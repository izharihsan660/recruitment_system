<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('candidate') !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'education' => ['nullable', 'array'],
            'education.*.degree' => ['required_with:education', 'string', 'max:255'],
            'education.*.major' => ['required_with:education', 'string', 'max:255'],
            'education.*.institution' => ['required_with:education', 'string', 'max:255'],
            'education.*.year' => ['required_with:education', 'integer'],
            'experience' => ['nullable', 'array'],
            'experience.*.company' => ['required_with:experience', 'string', 'max:255'],
            'experience.*.position' => ['required_with:experience', 'string', 'max:255'],
            'experience.*.start_year' => ['required_with:experience', 'integer'],
            'experience.*.end_year' => ['nullable', 'integer'],
            'experience.*.description' => ['nullable', 'string'],
        ];
    }
}
