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
            'status' => $this->status,
            'status_label' => $this->portalStatusLabel(),
            'rejection_reason' => $this->when($this->status === 'rejected', $this->rejection_reason),
            'rejection_stage' => $this->when($this->status === 'rejected', $this->rejection_stage),
            'consent' => $this->consent,
            'consent_at' => $this->consent_at,
            'withdrawn_at' => $this->withdrawn_at,
            'job_posting' => new JobPostingResource($this->whenLoaded('jobPosting')),
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'documents' => CandidateDocumentResource::collection($this->whenLoaded('documents')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
