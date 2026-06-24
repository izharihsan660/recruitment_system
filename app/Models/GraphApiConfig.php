<?php

namespace App\Models;

use Database\Factories\GraphApiConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraphApiConfig extends Model
{
    /** @use HasFactory<GraphApiConfigFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'client_secret',
        'calendar_user_email',
        'recruitment_mailbox',
        'is_active',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }
}
