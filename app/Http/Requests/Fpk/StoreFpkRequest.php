<?php

namespace App\Http\Requests\Fpk;

use App\Models\RecruitmentRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFpkRequest extends FormRequest
{
    public const FACILITY_KEYS = [
        'salary_gross',
        'salary_nett',
        'transport',
        'health',
        'communication',
        'meal',
        'overtime',
        'vehicle',
        'laptop',
        'mess',
        'apd',
        'uniform',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('create', RecruitmentRequest::class) ?? false;
    }

    public function rules(): array
    {
        $facilityRules = collect(self::FACILITY_KEYS)
            ->mapWithKeys(fn (string $key): array => ["facilities.$key" => ['required', 'boolean']])
            ->all();

        return [
            'entity_id' => ['required', 'integer', Rule::exists('entities', 'id')->where('is_active', true)],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('entity_id', $this->integer('entity_id'))->where('is_active', true)],
            'requester_position' => ['required', 'string', 'max:255'],
            'requested_at' => ['required', 'date'],
            'position_name' => ['required', 'string', 'max:255'],
            'headcount' => ['required', 'integer', 'min:1'],
            'employment_status' => ['required', Rule::in(['permanent', 'contract', 'intern'])],
            'job_title' => ['required', 'string', 'max:255'],
            'work_location' => ['required', 'string', 'max:255'],
            'required_at' => ['required', 'date'],
            'reason_type' => ['required', Rule::in(['replacement', 'addition', 'new_project', 'other'])],
            'reason_notes' => ['required', 'string'],
            'min_education' => ['required', 'string', 'max:255'],
            'min_experience' => ['required', 'string', 'max:255'],
            'required_skills' => ['required', 'string'],
            'age_min' => ['nullable', 'integer', 'min:15', 'max:100'],
            'age_max' => ['nullable', 'integer', 'min:15', 'max:100', 'gte:age_min'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'any'])],
            'job_description' => ['required', 'string'],
            'facilities' => ['required', 'array:'.implode(',', self::FACILITY_KEYS)],
            ...$facilityRules,
        ];
    }
}
