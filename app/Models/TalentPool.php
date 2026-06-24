<?php

namespace App\Models;

use Database\Factories\TalentPoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentPool extends Model
{
    /** @use HasFactory<TalentPoolFactory> */
    use HasFactory;

    protected $fillable = [
        'candidate_id', 'status', 'tags', 'notes', 'source_application_id', 'added_by', 'added_at',
    ];

    protected $attributes = ['status' => 'active'];

    protected function casts(): array
    {
        return ['tags' => 'array', 'added_at' => 'datetime'];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function sourceApplication(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'source_application_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
