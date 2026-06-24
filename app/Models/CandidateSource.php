<?php

namespace App\Models;

use Database\Factories\CandidateSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateSource extends Model
{
    /** @use HasFactory<CandidateSourceFactory> */
    use HasFactory;

    protected $fillable = ['name', 'is_active'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'source_id');
    }

    public function isReferral(): bool
    {
        return str($this->name)->lower()->value() === 'referral';
    }
}
