<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreboardingItem extends Model
{
    protected $fillable = ['checklist_id', 'title', 'description', 'assigned_to', 'status', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(PreboardingChecklist::class, 'checklist_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
