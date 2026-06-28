<?php

namespace App\Models;

use Database\Factories\DocusealConfigFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocusealConfig extends Model
{
    /** @use HasFactory<DocusealConfigFactory> */
    use HasFactory;

    protected $fillable = [
        'api_url',
        'api_key',
        'webhook_secret',
        'offering_template_id',
        'pkwt_template_id',
        'is_active',
    ];

    protected $hidden = [
        'api_key',
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
