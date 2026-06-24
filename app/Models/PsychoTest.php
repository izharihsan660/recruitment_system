<?php

namespace App\Models;

use Database\Factories\PsychoTestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsychoTest extends Model
{
    /** @use HasFactory<PsychoTestFactory> */
    use HasFactory;

    protected $fillable = [
        'application_id',
        'test_type',
        'scheduled_at',
        'notes',
        'decision',
        'rejection_reason',
        'conducted_by',
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

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }
}
