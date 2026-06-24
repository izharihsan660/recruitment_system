<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferingLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'entity_id', 'hr_signer_id', 'position_name', 'department', 'work_location',
        'contract_type', 'start_date', 'contract_duration', 'salary_gross', 'salary_nett', 'allowances',
        'expiry_date', 'docuseal_submission_id', 'hr_signing_url', 'candidate_signing_url', 'status',
        'rejection_reason', 'negotiation_notes', 'signed_at', 'pdf_path', 'sharepoint_url', 'archive_status',
        'archive_attempted_at', 'archive_error',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expiry_date' => 'date',
            'salary_gross' => 'integer',
            'salary_nett' => 'integer',
            'allowances' => 'array',
            'signed_at' => 'datetime',
            'archive_attempted_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function hrSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_signer_id');
    }
}
