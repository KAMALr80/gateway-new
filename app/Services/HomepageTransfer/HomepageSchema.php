<?php

namespace App\Services\HomepageTransfer;

use App\Rules\HttpsUrlOnHost;

/**
 * The explicit allowlist of what a homepage package may contain. Anything not listed here is neither exported
 * nor imported, so unrelated columns or settings can never travel in a package by accident.
 *
 * Every list mirrors Admin\HomepageConfigController: a package can only carry what the admin forms can save.
 */
final class HomepageSchema
{
    public const SECTION_TYPES = [
        'featured_category', 'banner', 'hero', 'product_carousel', 'brand_showcase', 'catalog_showcase',
    ];

    /** Item kinds each section type accepts (mirrors Admin\HomepageConfigController::validateItem). */
    public const ITEM_KINDS = [
        'hero' => ['slide'],
        'banner' => ['content'],
        'featured_category' => ['heading', 'content'],
        'product_carousel' => ['heading', 'content'],
        'brand_showcase' => ['heading', 'brand'],
        'catalog_showcase' => ['catalog'],
    ];

    /** A product section holds at most this many promotional banners (Admin\HomepageConfigController::storeItem). */
    public const MAX_CAROUSEL_BANNERS = 2;

    /** Keys of homepage_configs.site_settings that belong to the homepage (mirrors the admin update rules). */
    public const SITE_SETTING_KEYS = [
        'site_name', 'logo_url', 'phone', 'email', 'address', 'search_placeholder', 'login_text', 'register_text',
        'sale_label', 'sale_url', 'newsletter_enabled', 'newsletter_title', 'newsletter_placeholder',
        'newsletter_button_text', 'seo_title', 'seo_description', 'homepage_heading', 'catalog_background_logo_url',
        'footer_copyright', 'footer_legal_notice', 'business_hours_title', 'nicotine_warning', 'app_store_url',
        'play_store_url', 'nav_groups', 'footer_columns', 'business_hours',
    ];

    /** Plain (non-reference) keys of homepage_sections.settings per section type. */
    public const SECTION_SETTING_KEYS = [
        'product_carousel' => ['limit', 'badge'],
        'featured_category' => ['product_limit'],
        'brand_showcase' => [],
        'hero' => ['subtitle', 'cta_text', 'cta_url', 'interval_ms'],
        'banner' => [],
        'catalog_showcase' => ['eyebrow', 'heading_phrases', 'description'],
    ];

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public const CLIENT_KEY_PATTERN = '/^[a-z0-9\-_]{1,64}$/';

    /** Laravel validation rules for the allowlisted site settings (same limits as the admin form). */
    public static function siteSettingRules(string $prefix = 'site_settings'): array
    {
        return [
            "$prefix" => 'array',
            "$prefix.site_name" => 'nullable|string|max:255',
            "$prefix.logo_url" => 'nullable|url:http,https|max:2048',
            "$prefix.phone" => 'nullable|string|max:40',
            "$prefix.email" => 'nullable|email|max:255',
            "$prefix.address" => 'nullable|string|max:1000',
            "$prefix.search_placeholder" => 'nullable|string|max:255',
            "$prefix.login_text" => 'nullable|string|max:80',
            "$prefix.register_text" => 'nullable|string|max:80',
            "$prefix.sale_label" => 'nullable|string|max:80',
            "$prefix.sale_url" => 'nullable|string|max:2048',
            "$prefix.newsletter_enabled" => 'boolean',
            "$prefix.newsletter_title" => 'nullable|string|max:255',
            "$prefix.newsletter_placeholder" => 'nullable|string|max:255',
            "$prefix.newsletter_button_text" => 'nullable|string|max:80',
            "$prefix.seo_title" => 'nullable|string|max:255',
            "$prefix.seo_description" => 'nullable|string|max:500',
            "$prefix.homepage_heading" => 'nullable|string|max:255',
            "$prefix.catalog_background_logo_url" => 'nullable|url:http,https|max:2048',
            "$prefix.footer_copyright" => 'nullable|string|max:500',
            "$prefix.footer_legal_notice" => 'nullable|string|max:3000',
            "$prefix.business_hours_title" => 'nullable|string|max:120',
            "$prefix.nicotine_warning" => 'nullable|string|max:300',
            "$prefix.app_store_url" => ['nullable', 'string', 'max:2048', new HttpsUrlOnHost('apps.apple.com')],
            "$prefix.play_store_url" => ['nullable', 'string', 'max:2048', new HttpsUrlOnHost('play.google.com')],
            "$prefix.nav_groups" => 'array|max:30',
            "$prefix.nav_groups.*.label" => 'required|string|max:80',
            "$prefix.nav_groups.*.keywords" => 'array|max:30',
            "$prefix.nav_groups.*.keywords.*" => 'string|max:80',
            "$prefix.nav_groups.*.url" => 'nullable|string|max:2048',
            "$prefix.footer_columns" => 'array|max:8',
            "$prefix.footer_columns.*.title" => 'required|string|max:80',
            "$prefix.footer_columns.*.links" => 'array|max:30',
            "$prefix.footer_columns.*.links.*.label" => 'required|string|max:120',
            "$prefix.footer_columns.*.links.*.url" => 'required|string|max:2048',
            "$prefix.business_hours" => 'array|max:14',
            "$prefix.business_hours.*" => 'string|max:120',
        ];
    }

    /** Laravel validation rules for the plain settings of one section type. */
    public static function sectionSettingRules(string $type, string $prefix): array
    {
        return match ($type) {
            'featured_category' => ["$prefix.product_limit" => 'nullable|integer|min:1|max:50'],
            'hero' => [
                "$prefix.subtitle" => 'nullable|string|max:500',
                "$prefix.cta_text" => 'nullable|string|max:100',
                "$prefix.cta_url" => 'nullable|string|max:2048',
                "$prefix.interval_ms" => 'nullable|integer|min:2000|max:30000',
            ],
            'product_carousel' => [
                "$prefix.limit" => 'nullable|integer|min:1|max:50',
                "$prefix.badge" => 'nullable|string|max:20',
            ],
            'catalog_showcase' => [
                "$prefix.eyebrow" => 'nullable|string|max:100',
                "$prefix.heading_phrases" => 'nullable|array|max:10',
                "$prefix.heading_phrases.*" => 'string|max:100',
                "$prefix.description" => 'nullable|string|max:1000',
            ],
            default => [],
        };
    }
}
