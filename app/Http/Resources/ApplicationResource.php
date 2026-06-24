<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_posting_id' => $this->job_posting_id,
            'candidate_id' => $this->candidate_id,
            'source' => $this->source,
            'source_id' => $this->source_id,
            'referral_name' => $this->referral_name,
            'referral_department' => $this->referral_department,
            'referral_phone' => $this->referral_phone,
            'referral_relation' => $this->referral_relation,
            'referral_notes' => $this->referral_notes,
            'input_by' => $this->input_by,
            'status' => $this->status,
            'status_label' => $this->portalStatusLabel(),
            'rejection_reason' => $this->when($this->status === 'rejected', $this->rejection_reason),
            'rejection_stage' => $this->when($this->status === 'rejected', $this->rejection_stage),
            'consent' => $this->consent,
            'consent_at' => $this->consent_at,
            'consent_by' => $this->consent_by,
            'withdrawn_at' => $this->withdrawn_at,
            'job_posting' => new JobPostingResource($this->whenLoaded('jobPosting')),
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'documents' => CandidateDocumentResource::collection($this->whenLoaded('documents')),
            'pipeline_logs' => PipelineLogResource::collection($this->whenLoaded('pipelineLogs')),
            'screening' => new ScreeningResource($this->whenLoaded('screening')),
            'psycho_test' => new PsychoTestResource($this->whenLoaded('psychoTest')),
            'hr_interview' => new HrInterviewResource($this->whenLoaded('hrInterview')),
            'user_interview' => new UserInterviewResource($this->whenLoaded('userInterview')),
            'background_check' => $this->whenLoaded('backgroundCheck'),
            'offering_letter' => $this->whenLoaded('offeringLetter'),
            'pkwt_contract' => $this->whenLoaded('pkwtContract'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
