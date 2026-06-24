<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalChain extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalChainFactory> */
    use HasFactory;

    protected $fillable = [
        'department_id',
        'level',
        'type',
        'approver_user_id',
        'approver_role',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
