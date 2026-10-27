<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A storefront newsletter sign-up. Only the e-mail address is stored (no IP address or other data). */
class NewsletterSubscription extends Model
{
    protected $fillable = ['homepage_config_id', 'email'];

    public function homepageConfig(): BelongsTo
    {
        return $this->belongsTo(HomepageConfig::class);
    }
}
