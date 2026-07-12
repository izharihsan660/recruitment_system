<?php

namespace App\Models;

use Database\Factories\GraphMailSenderSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraphMailSenderSetting extends Model
{
    /** @use HasFactory<GraphMailSenderSettingFactory> */
    use HasFactory;

    protected $fillable = ['tenant_id', 'client_id', 'client_secret', 'sender_mailbox', 'from_name', 'is_active'];

    protected $hidden = ['client_secret'];

    protected function casts(): array
    {
        return ['client_secret' => 'encrypted', 'is_active' => 'boolean'];
    }
}
