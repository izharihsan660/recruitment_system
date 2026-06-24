<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [
        'application_id', 'candidate_id', 'entity_id', 'department_id', 'employee_id', 'full_name', 'email', 'phone',
        'position_name', 'contract_type', 'start_date', 'end_date', 'status', 'activated_by', 'activated_at',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'activated_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function preboardingChecklist(): HasOne
    {
        return $this->hasOne(PreboardingChecklist::class);
    }

    public function probationRecord(): HasOne
    {
        return $this->hasOne(ProbationRecord::class);
    }
}
