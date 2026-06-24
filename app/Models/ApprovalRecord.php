<?php

namespace App\Models;

use Database\Factories\ApprovalRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRecord extends Model
{
    /** @use HasFactory<ApprovalRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'recruitment_request_id',
        'approval_chain_id',
        'level',
        'approver_id',
        'action',
        'comment',
        'acted_at',
    ];

    protected $attributes = [
        'action' => 'waiting',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'acted_at' => 'datetime',
        ];
    }

    public function recruitmentRequest(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }

    public function approvalChain(): BelongsTo
    {
        return $this->belongsTo(ApprovalChain::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
