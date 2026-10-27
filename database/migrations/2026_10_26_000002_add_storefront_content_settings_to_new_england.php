<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Moves content the New England storefront used to hard-code into its homepage config, so it is editable in the
 * admin: the nicotine warning bar, the app-store links and the "New" badge on the New Arrivals row.
 * Only keys that are not set yet are filled; other clients are untouched.
 */
return new class extends Migration
{
    private const CLIENT = 'new-england';

    private const SITE_DEFAULTS = [
        'nicotine_warning' => 'WARNING: This product contains nicotine. Nicotine is an addictive chemical.',
        'app_store_url' => 'https://apps.apple.com/in/app/new-england-distribution/id6741199522',
        'play_store_url' => 'https://play.google.com/store/apps/details?id=com.brainbean.newengland',
    ];

    private const BADGE = 'New';

    public function up(): void
    {
        $config = DB::table('homepage_configs')->where('client_key', self::CLIENT)->first();
        if (! $config) {
            return;
        }

        $site = json_decode($config->site_settings ?? '[]', true) ?: [];
        foreach (self::SITE_DEFAULTS as $key => $value) {
            if (! array_key_exists($key, $site)) {
                $site[$key] = $value;
            }
        }
        DB::table('homepage_configs')->where('id', $config->id)->update(['site_settings' => json_encode($site)]);

        $sections = DB::table('homepage_sections')
            ->where('homepage_config_id', $config->id)
            ->where('type', 'product_carousel')
            ->get();
        foreach ($sections as $section) {
            $settings = json_decode($section->settings ?? '[]', true) ?: [];
            if (! array_key_exists('badge', $settings) && preg_match('/new arrivals?/i', (string) $section->title)) {
                $settings['badge'] = self::BADGE;
                DB::table('homepage_sections')->where('id', $section->id)->update(['settings' => json_encode($settings)]);
            }
        }
    }

    public function down(): void
    {
        $config = DB::table('homepage_configs')->where('client_key', self::CLIENT)->first();
        if (! $config) {
            return;
        }

        // Remove only values that are still the ones this migration added.
        $site = json_decode($config->site_settings ?? '[]', true) ?: [];
        foreach (self::SITE_DEFAULTS as $key => $value) {
            if (($site[$key] ?? null) === $value) {
                unset($site[$key]);
            }
        }
        DB::table('homepage_configs')->where('id', $config->id)->update(['site_settings' => json_encode($site)]);

        $sections = DB::table('homepage_sections')->where('homepage_config_id', $config->id)->where('type', 'product_carousel')->get();
        foreach ($sections as $section) {
            $settings = json_decode($section->settings ?? '[]', true) ?: [];
            if (($settings['badge'] ?? null) === self::BADGE) {
                unset($settings['badge']);
                DB::table('homepage_sections')->where('id', $section->id)->update(['settings' => json_encode($settings)]);
            }
        }
    }
};
