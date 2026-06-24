<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentPoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'status' => $this->status,
            'tags' => $this->tags,
            'notes' => $this->notes,
            'source_application_id' => $this->source_application_id,
            'added_by' => $this->added_by,
            'added_at' => $this->added_at,
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'source_application' => new ApplicationResource($this->whenLoaded('sourceApplication')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
