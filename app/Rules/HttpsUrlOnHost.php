<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An absolute https:// URL on one exact host (no credentials, no port). Used for links the storefront sends
 * shoppers to directly, such as the app-store listings, so a settings change cannot point them elsewhere.
 */
class HttpsUrlOnHost implements ValidationRule
{
    public function __construct(private readonly string $host) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = is_string($value) && filter_var($value, FILTER_VALIDATE_URL) ? parse_url($value) : false;

        if (
            ! $parts
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || strtolower($parts['host'] ?? '') !== $this->host
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
        ) {
            $fail("The :attribute must be an https://{$this->host} link.");
        }
    }
}
