<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McuSimperRecord extends Model
{
    protected $fillable = [
        'application_id', 'mcu_required', 'mcu_scheduled_at', 'mcu_location', 'mcu_result_path', 'mcu_status', 'mcu_notes',
        'simper_required', 'simper_scheduled_at', 'simper_location', 'simper_result_path', 'simper_status', 'simper_notes',
        'rejection_reason', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'mcu_required' => 'boolean',
            'mcu_scheduled_at' => 'datetime',
            'simper_required' => 'boolean',
            'simper_scheduled_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
