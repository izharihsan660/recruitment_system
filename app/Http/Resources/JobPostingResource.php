<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobPostingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recruitment_request_id' => $this->recruitment_request_id,
            'entity_id' => $this->entity_id,
            'department_id' => $this->department_id,
            'position_name' => $this->position_name,
            'employment_status' => $this->employment_status,
            'work_location' => $this->work_location,
            'job_description' => $this->job_description,
            'requirements' => $this->requirements,
            'mcu_required' => $this->mcu_required,
            'simper_required' => $this->simper_required,
            'status' => $this->status,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'entity' => new EntityResource($this->whenLoaded('entity')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'recruitment_request' => new RecruitmentRequestResource($this->whenLoaded('recruitmentRequest')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
