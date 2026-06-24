<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraphApiConfig extends Model
{
    /** @use HasFactory<\Database\Factories\GraphApiConfigFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'client_secret',
        'calendar_user_email',
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
