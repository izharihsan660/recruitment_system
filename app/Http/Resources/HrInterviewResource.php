<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HrInterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'scheduled_at' => $this->scheduled_at,
            'teams_meeting_link' => $this->teams_meeting_link,
            'teams_meeting_id' => $this->teams_meeting_id,
            'interviewer' => new UserResource($this->whenLoaded('interviewer')),
            'score_communication' => $this->score_communication,
            'score_personality' => $this->score_personality,
            'score_motivation' => $this->score_motivation,
            'score_attitude' => $this->score_attitude,
            'score_culture_fit' => $this->score_culture_fit,
            'strengths' => $this->strengths,
            'weaknesses' => $this->weaknesses,
            'salary_expectation' => $this->salary_expectation,
            'recommendation' => $this->recommendation,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }
}
