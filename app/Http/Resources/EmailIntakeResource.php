<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailIntakeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'graph_message_id' => $this->graph_message_id,
            'sender_name' => $this->sender_name,
            'sender_email' => $this->sender_email,
            'subject' => $this->subject,
            'body' => $this->body,
            'received_at' => $this->received_at,
            'attachment_path' => $this->attachment_path,
            'suggested_job_id' => $this->suggested_job_id,
            'status' => $this->status,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'rejection_reason' => $this->rejection_reason,
            'candidate_id' => $this->candidate_id,
            'is_duplicate' => $this->is_duplicate,
            'suggested_job' => new JobPostingResource($this->whenLoaded('suggestedJob')),
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
