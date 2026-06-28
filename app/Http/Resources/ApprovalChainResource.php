<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalChainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'level' => $this->level,
            'type' => $this->type,
            'approver_user_id' => $this->approver_user_id,
            'approver_role' => $this->approver_role,
            'has_records' => $this->whenCounted('approvalRecords', fn (): bool => $this->approval_records_count > 0),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'approver_user' => new UserResource($this->whenLoaded('approverUser')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
