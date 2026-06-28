<?php

namespace App\Http\Requests;

use App\Models\CandidateSource;
use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

class StoreHrCandidateTalentPoolInputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Roles::Admin, Roles::HrRecruiter, Roles::HrManager]) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'education_level' => ['required', 'string'],
            'education_major' => ['nullable', 'string'],
            'education_institution' => ['nullable', 'string'],
            'experience_company' => ['nullable', 'string'],
            'experience_position' => ['nullable', 'string'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'source_id' => ['nullable', 'integer', 'exists:candidate_sources,id'],
            'referral_name' => ['nullable', 'string', 'max:255'],
            'referral_department' => ['nullable', 'string', 'max:255'],
            'referral_phone' => ['nullable', 'string', 'max:255'],
            'referral_relation' => ['nullable', 'string', 'max:255'],
            'referral_notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,passive,hot_prospect,on_hold,do_not_contact,hired_elsewhere,archived'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'consent' => ['required', 'boolean', 'accepted'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $sourceId = $this->integer('source_id');

            if ($sourceId <= 0) {
                return;
            }

            $source = CandidateSource::query()->find($sourceId);

            if ($source?->isReferral() !== true) {
                return;
            }

            foreach (['referral_name', 'referral_department', 'referral_phone', 'referral_relation'] as $field) {
                if (blank($this->input($field))) {
                    $validator->errors()->add($field, 'Field wajib diisi untuk source Referral.');
                }
            }
        }];
    }
}
