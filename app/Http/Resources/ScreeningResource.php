<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScreeningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'education_match' => $this->education_match,
            'experience_match' => $this->experience_match,
            'document_complete' => $this->document_complete,
            'notes' => $this->notes,
            'decision' => $this->decision,
            'rejection_reason' => $this->rejection_reason,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'reviewed_at' => $this->reviewed_at,
        ];
    }
}
