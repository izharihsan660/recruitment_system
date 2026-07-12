<?php

namespace App\Models;

use Database\Factories\EmailIntakeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailIntake extends Model
{
    /** @use HasFactory<EmailIntakeFactory> */
    use HasFactory;

    protected $fillable = [
        'graph_message_id', 'sender_name', 'sender_email', 'subject', 'body', 'phone_number', 'received_at', 'attachment_path',
        'suggested_job_id', 'status', 'reviewed_by', 'reviewed_at', 'rejection_reason', 'candidate_id', 'is_duplicate',
    ];

    protected $attributes = ['status' => 'need_review', 'is_duplicate' => false];

    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'reviewed_at' => 'datetime', 'is_duplicate' => 'boolean'];
    }

    public function suggestedJob(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'suggested_job_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
