<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PipelineLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'from_stage' => $this->from_stage,
            'to_stage' => $this->to_stage,
            'actor_id' => $this->actor_id,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
