<?php

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'job_posting_id', 'candidate_id', 'source', 'source_id', 'referral_name', 'referral_department',
        'referral_phone', 'referral_relation', 'referral_notes', 'input_by', 'status', 'rejection_reason',
        'rejection_stage', 'consent', 'consent_at', 'consent_by', 'withdrawn_at',
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

    public function candidateSource(): BelongsTo
    {
        return $this->belongsTo(CandidateSource::class, 'source_id');
    }

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function consentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consent_by');
    }

    public function pipelineLogs(): HasMany
    {
        return $this->hasMany(PipelineLog::class);
    }

    public function screening(): HasOne
    {
        return $this->hasOne(Screening::class);
    }

    public function psychoTest(): HasOne
    {
        return $this->hasOne(PsychoTest::class);
    }

    public function hrInterview(): HasOne
    {
        return $this->hasOne(HrInterview::class);
    }

    public function userInterview(): HasOne
    {
        return $this->hasOne(UserInterview::class);
    }

    public function backgroundCheck(): HasOne
    {
        return $this->hasOne(BackgroundCheck::class);
    }

    public function offeringLetter(): HasOne
    {
        return $this->hasOne(OfferingLetter::class);
    }

    public function pkwtContract(): HasOne
    {
        return $this->hasOne(PkwtContract::class);
    }

    public function mcuSimperRecord(): HasOne
    {
        return $this->hasOne(McuSimperRecord::class);
    }

    public function hiringDecision(): HasOne
    {
        return $this->hasOne(HiringDecision::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class);
    }

    public function portalStatusLabel(): string
    {
        return match ($this->status) {
            'applied', 'screening', 'test', 'test_psikotes' => 'Lamaran Sedang Diproses',
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
