<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PsychoTestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'test_type' => $this->test_type,
            'scheduled_at' => $this->scheduled_at,
            'notes' => $this->notes,
            'decision' => $this->decision,
            'rejection_reason' => $this->rejection_reason,
            'conductor' => new UserResource($this->whenLoaded('conductor')),
        ];
    }
}
