<?php

namespace App\Models;

use Database\Factories\RecruitmentRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentRequest extends Model
{
    /** @use HasFactory<RecruitmentRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'entity_id', 'department_id', 'requester_id', 'requester_position', 'requested_at',
        'position_name', 'headcount', 'employment_status', 'job_title', 'work_location', 'required_at',
        'reason_type', 'reason_notes', 'min_education', 'min_experience', 'required_skills',
        'age_min', 'age_max', 'gender', 'job_description', 'facilities', 'status',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'date',
            'required_at' => 'date',
            'headcount' => 'integer',
            'age_min' => 'integer',
            'age_max' => 'integer',
            'facilities' => 'array',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approvalRecords(): HasMany
    {
        return $this->hasMany(ApprovalRecord::class)->latest('id');
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }
}
