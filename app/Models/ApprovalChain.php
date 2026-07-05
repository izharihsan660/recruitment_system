<?php

namespace App\Models;

use Database\Factories\ApprovalChainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalChain extends Model
{
    /** @use HasFactory<ApprovalChainFactory> */
    use HasFactory;

    protected $fillable = [
        'department_id',
        'type',
        'approver_user_id',
        'approver_role',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function approvalRecords(): HasMany
    {
        return $this->hasMany(ApprovalRecord::class);
    }
}
