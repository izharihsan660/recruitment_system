<?php

namespace App\Http\Requests\Fpk;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comment' => [Rule::requiredIf(fn (): bool => in_array($this->route()?->getActionMethod(), ['reject', 'needRevision'], true)), 'nullable', 'string'],
        ];
    }
}
