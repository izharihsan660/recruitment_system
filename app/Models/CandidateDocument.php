<?php

namespace App\Models;

use Database\Factories\CandidateDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateDocument extends Model
{
    /** @use HasFactory<CandidateDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'candidate_id', 'application_id', 'document_type', 'file_path', 'original_name', 'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
