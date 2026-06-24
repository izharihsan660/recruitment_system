<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recruitment_request_id' => $this->recruitment_request_id,
            'approval_chain_id' => $this->approval_chain_id,
            'level' => $this->level,
            'approver_id' => $this->approver_id,
            'approver' => new UserResource($this->whenLoaded('approver')),
            'action' => $this->action,
            'comment' => $this->comment,
            'acted_at' => $this->acted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
