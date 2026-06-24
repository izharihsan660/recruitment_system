<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CandidateDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'application_id' => $this->application_id,
            'document_type' => $this->document_type,
            'file_path' => $this->file_path,
            'file_url' => Storage::disk('public')->url($this->file_path),
            'original_name' => $this->original_name,
            'uploaded_at' => $this->uploaded_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
