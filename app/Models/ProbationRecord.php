<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProbationRecord extends Model
{
    protected $fillable = ['employee_id', 'start_date', 'day30_due', 'day60_due', 'day90_due', 'extended_until', 'extension_count', 'status', 'final_outcome'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'day30_due' => 'date', 'day60_due' => 'date', 'day90_due' => 'date', 'extended_until' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ProbationEvaluation::class, 'probation_id');
    }
}
