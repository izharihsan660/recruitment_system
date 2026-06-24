<?php

namespace App\Models;

use Database\Factories\PipelineLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineLog extends Model
{
    /** @use HasFactory<PipelineLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['application_id', 'from_stage', 'to_stage', 'actor_id', 'notes'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
