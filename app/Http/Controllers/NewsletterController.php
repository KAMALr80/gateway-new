<?php

namespace App\Http\Controllers;

use App\Models\HomepageConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    /**
     * Storefront newsletter sign-up (POST /api/newsletter). The answer is the same whether the address is new or
     * already subscribed, so the endpoint cannot be used to find out who is on the list.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'client' => 'required|string|max:64',
            'email' => 'required|string|email:rfc|max:255',
            // Honeypot: real visitors never see this field.
            'website' => 'nullable|string|max:255',
        ]);

        $config = HomepageConfig::where('client_key', $data['client'])->where('is_active', true)->first();
        if (! $config || ! filter_var($config->site_settings['newsletter_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return response()->json(['message' => 'Newsletter signup is not available.'], 404);
        }

        if (blank($data['website'] ?? null)) {
            $config->newsletterSubscriptions()->firstOrCreate(['email' => Str::lower(trim($data['email']))]);
        }

        return response()->json(['message' => 'Thanks for subscribing.']);
    }
}
