<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserInterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'scheduled_at' => $this->scheduled_at,
            'location' => $this->location,
            'interviewer' => new UserResource($this->whenLoaded('interviewer')),
            'score_technical' => $this->score_technical,
            'score_experience' => $this->score_experience,
            'score_problem_solving' => $this->score_problem_solving,
            'score_team_fit' => $this->score_team_fit,
            'recommendation' => $this->recommendation,
            'rejection_reason' => $this->rejection_reason,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }
}
