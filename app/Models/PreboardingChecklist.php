<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreboardingChecklist extends Model
{
    protected $fillable = ['employee_id', 'status', 'first_day'];

    protected function casts(): array
    {
        return ['first_day' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PreboardingItem::class, 'checklist_id');
    }
}
