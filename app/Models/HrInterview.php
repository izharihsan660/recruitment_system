<?php

namespace App\Models;

use Database\Factories\HrInterviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrInterview extends Model
{
    /** @use HasFactory<HrInterviewFactory> */
    use HasFactory;

    protected $fillable = [
        'application_id',
        'scheduled_at',
        'teams_meeting_link',
        'teams_meeting_id',
        'interviewer_id',
        'score_communication',
        'score_personality',
        'score_motivation',
        'score_attitude',
        'score_culture_fit',
        'strengths',
        'weaknesses',
        'salary_expectation',
        'recommendation',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'salary_expectation' => 'integer',
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
