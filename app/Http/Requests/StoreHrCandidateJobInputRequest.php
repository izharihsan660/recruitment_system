<?php

namespace App\Http\Requests;

use App\Models\CandidateSource;
use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

class StoreHrCandidateJobInputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Roles::HrRecruiter, Roles::HrManager]) === true;
    }

    public function rules(): array
    {
        return [
            'job_posting_id' => ['required', 'integer', 'exists:job_postings,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'source_id' => ['nullable', 'integer', 'exists:candidate_sources,id'],
            'referral_name' => ['nullable', 'string', 'max:255'],
            'referral_department' => ['nullable', 'string', 'max:255'],
            'referral_phone' => ['nullable', 'string', 'max:255'],
            'referral_relation' => ['nullable', 'string', 'max:255'],
            'referral_notes' => ['nullable', 'string'],
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
