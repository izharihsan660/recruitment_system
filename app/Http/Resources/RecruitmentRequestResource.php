<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_id' => $this->entity_id,
            'department_id' => $this->department_id,
            'requester_id' => $this->requester_id,
            'requester_position' => $this->requester_position,
            'requested_at' => $this->requested_at?->toDateString(),
            'position_name' => $this->position_name,
            'headcount' => $this->headcount,
            'employment_status' => $this->employment_status,
            'job_title' => $this->job_title,
            'work_location' => $this->work_location,
            'required_at' => $this->required_at?->toDateString(),
            'reason_type' => $this->reason_type,
            'reason_notes' => $this->reason_notes,
            'min_education' => $this->min_education,
            'min_experience' => $this->min_experience,
            'required_skills' => $this->required_skills,
            'age_min' => $this->age_min,
            'age_max' => $this->age_max,
            'gender' => $this->gender,
            'job_description' => $this->job_description,
            'facilities' => $this->facilities,
            'status' => $this->status,
            'current_approval_level' => $this->current_approval_level,
            'entity' => new EntityResource($this->whenLoaded('entity')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'requester' => new UserResource($this->whenLoaded('requester')),
            'approval_records' => ApprovalRecordResource::collection($this->whenLoaded('approvalRecords')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
