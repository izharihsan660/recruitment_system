<?php

namespace App\Models;

use Database\Factories\UserInterviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInterview extends Model
{
    /** @use HasFactory<UserInterviewFactory> */
    use HasFactory;

    protected $fillable = [
        'application_id',
        'scheduled_at',
        'location',
        'interviewer_id',
        'score_technical',
        'score_experience',
        'score_problem_solving',
        'score_team_fit',
        'recommendation',
        'rejection_reason',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }
}
