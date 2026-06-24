<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PkwtContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'entity_id', 'company_signer_id', 'candidate_id', 'position_name', 'department',
        'work_location', 'contract_type', 'start_date', 'end_date', 'contract_duration', 'salary_gross',
        'salary_nett', 'allowances', 'docuseal_submission_id', 'candidate_signing_url', 'company_signing_url',
        'status', 'signed_at', 'pdf_path', 'sharepoint_url', 'archive_status', 'archive_attempted_at', 'archive_error',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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

    public function companySigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_signer_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
