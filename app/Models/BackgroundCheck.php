<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackgroundCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'ktp_verified', 'ijazah_verified', 'certificate_verified', 'reference_verified',
        'notes', 'decision', 'rejection_reason', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'ktp_verified' => 'boolean',
            'ijazah_verified' => 'boolean',
            'certificate_verified' => 'boolean',
            'reference_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
