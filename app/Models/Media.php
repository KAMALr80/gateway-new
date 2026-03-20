<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'disk',
        'path',
        'url',
        'thumbnail_url',
        'alt',
        'mime_type',
        'size',
        'width',
        'height',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'size'       => 'integer',
        'width'      => 'integer',
        'height'     => 'integer',
        'sort_order' => 'integer',
    ];

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    // Always returns a usable public URL regardless of storage driver
    public function getPublicUrlAttribute(): string
    {
        if ($this->url) {
            return $this->url;
        }

        return Storage::disk($this->disk ?? 'public')->url($this->path);
    }
}