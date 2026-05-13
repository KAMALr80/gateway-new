<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageSection extends Model
{
    protected $fillable = [
        'homepage_config_id',
        'type',
        'title',
        'sort_order',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
        'sort_order' => 'integer',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(HomepageConfig::class, 'homepage_config_id');
    }
}
