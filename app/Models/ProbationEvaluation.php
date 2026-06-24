<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProbationEvaluation extends Model
{
    protected $fillable = ['probation_id', 'milestone', 'evaluator_id', 'performance_notes', 'recommendation', 'evaluated_at'];

    protected function casts(): array
    {
        return ['evaluated_at' => 'datetime'];
    }

    public function probation(): BelongsTo
    {
        return $this->belongsTo(ProbationRecord::class, 'probation_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
