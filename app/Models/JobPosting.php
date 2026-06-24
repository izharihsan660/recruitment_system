<?php

namespace App\Models;

use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory;

    protected $fillable = [
        'recruitment_request_id', 'entity_id', 'department_id', 'position_name', 'employment_status',
        'work_location', 'job_description', 'requirements', 'test_required', 'mcu_required', 'simper_required',
        'status', 'opened_at', 'closed_at',
    ];

    protected $attributes = [
        'status' => 'draft',
        'test_required' => true,
        'mcu_required' => false,
        'simper_required' => false,
    ];

    protected function casts(): array
    {
        return [
            'test_required' => 'boolean',
            'mcu_required' => 'boolean',
            'simper_required' => 'boolean',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function recruitmentRequest(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['draft', 'open'], true);
    }
}
