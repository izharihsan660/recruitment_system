<?php

namespace App\Models;

use Database\Factories\EmailIntakeSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailIntakeSetting extends Model
{
    /** @use HasFactory<EmailIntakeSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'client_secret',
        'mailbox_address',
        'is_active',
        'last_synced_at',
        'last_received_at',
        'sync_interval_minutes',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'last_received_at' => 'datetime',
            'sync_interval_minutes' => 'integer',
        ];
    }
}
