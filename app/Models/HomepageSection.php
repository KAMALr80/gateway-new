<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function items(): HasMany
    {
        return $this->hasMany(HomepageSectionItem::class)->orderBy('sort_order');
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(HomepageSectionItem::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }
}
