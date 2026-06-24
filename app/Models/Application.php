<?php

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'job_posting_id', 'candidate_id', 'source', 'status', 'rejection_reason', 'rejection_stage',
        'consent', 'consent_at', 'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'consent' => 'boolean',
            'consent_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class);
    }

    public function portalStatusLabel(): string
    {
        return match ($this->status) {
            'applied', 'screening', 'test' => 'Lamaran Sedang Diproses',
            'interview_hr', 'interview_user' => 'Tahap Interview',
            'background_check', 'mcu_simper' => 'Tahap Verifikasi',
            'offering', 'hiring_decision' => 'Tahap Penawaran',
            'pkwt', 'hired' => 'Diterima',
            'rejected' => 'Tidak Dilanjutkan — '.$this->rejection_stage.' — '.$this->rejection_reason,
            'withdrawn' => 'Lamaran Dibatalkan',
            default => 'Lamaran Sedang Diproses',
        };
    }
}
