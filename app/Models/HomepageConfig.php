<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomepageConfig extends Model
{
    protected $fillable = [
        'client_key',
        'label',
        'site_settings',
        'site_logo_path',
        'catalog_background_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'site_settings' => 'array',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(HomepageSection::class)->orderBy('sort_order');
    }

    public function activeSections(): HasMany
    {
        return $this->hasMany(HomepageSection::class)->where('is_active', true)->orderBy('sort_order');
    }
}
