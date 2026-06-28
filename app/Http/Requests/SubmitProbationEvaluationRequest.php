<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmitProbationEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('notes') && ! $this->has('performance_notes')) {
            $this->merge(['performance_notes' => $this->input('notes')]);
        }
    }

    public function rules(): array
    {
        return [
            'milestone' => ['required', Rule::in(['day30', 'day60', 'day90', 'extended'])],
            'performance_notes' => ['required', 'string'],
            'recommendation' => ['nullable', Rule::requiredIf(fn (): bool => in_array($this->input('milestone'), ['day90', 'extended'], true)), Rule::in(['permanent', 'extended', 'terminated'])],
            'extended_start_date' => ['nullable', 'required_if:recommendation,extended', 'date'],
            'extended_end_date' => ['nullable', 'required_if:recommendation,extended', 'date', 'after:extended_start_date'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('milestone') === 'extended' && $this->input('recommendation') === 'extended') {
                    $validator->errors()->add('recommendation', 'Probation extended tidak bisa diperpanjang lagi.');
                }
            },
        ];
    }
}
