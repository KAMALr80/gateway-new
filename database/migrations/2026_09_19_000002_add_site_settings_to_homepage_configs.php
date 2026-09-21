<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_configs', function (Blueprint $table) {
            $table->json('site_settings')->nullable()->after('label');
            $table->string('site_logo_path')->nullable()->after('site_settings');
            $table->string('catalog_background_path')->nullable()->after('site_logo_path');
        });

        DB::table('homepage_configs')->whereNull('site_settings')->update([
            'site_settings' => json_encode([
                'site_name' => 'New England Distribution',
                'logo_url' => '/images/brand/new-england-logo-clean.png',
                'catalog_background_logo_url' => '/images/brand/new-england-logo.png',
                'phone' => '+1 617-548-6419',
                'email' => 'sales@newenglanddistro.com',
                'address' => "460 Amherst Street\nNashua, New Hampshire 03063",
                'search_placeholder' => 'Search for products, brands or categories',
                'login_text' => 'Login',
                'register_text' => 'Register for Wholesale',
                'sale_label' => 'On Sale',
                'sale_url' => '/sale',
                'newsletter_enabled' => true,
                'newsletter_title' => 'Signup To Newsletter',
                'newsletter_placeholder' => 'Enter your email address',
                'newsletter_button_text' => 'SignUp',
                'seo_title' => 'New England Distribution',
                'seo_description' => 'Wholesale distribution catalog and products.',
                'homepage_heading' => 'Wholesale Disposable Vapes in New Hampshire',
                'footer_copyright' => 'New England Distribution. – All Rights Reserved',
                'footer_legal_notice' => 'WARNING: This product is intended for use by persons 21 or older. Nicotine is highly addictive and habit forming. Keep out of reach of children.',
                'business_hours_title' => 'Business Hours',
                'nav_groups' => [
                    ['label' => 'Cigar', 'keywords' => ['cigar']],
                    ['label' => 'C-Store', 'keywords' => ['general', 'battery', 'detox', 'synthetic', 'incense']],
                    ['label' => 'Flower / Wax / Oil', 'keywords' => ['flower', 'wax', 'oil', 'cbd', 'hemp']],
                    ['label' => 'Glass', 'keywords' => ['glass', 'bong', 'pipe', 'ceramic']],
                    ['label' => 'Hookah', 'keywords' => ['hookah']],
                    ['label' => 'Kratom', 'keywords' => ['kratom']],
                    ['label' => 'Vape Shop', 'keywords' => ['vape', 'disposable', 'e-liquid', 'nicotine', 'pod']],
                    ['label' => '7-Hydroxymitragynine', 'keywords' => ['hydroxy', 'alkaloid']],
                    ['label' => 'Tobacco', 'keywords' => ['tobacco', 'wrap', 'leaf']],
                    ['label' => 'Roll Your Own', 'keywords' => ['rolling', 'paper', 'filter', 'grinder', 'cone']],
                    ['label' => 'Torch It', 'keywords' => ['torch', 'lighter', 'butane']],
                    ['label' => 'Whip Cream', 'keywords' => ['whip', 'cream', 'charger']],
                ],
                'footer_columns' => [
                    ['title' => 'Categories', 'links' => [
                        ['label' => 'Disposables', 'url' => '/shop?search=disposables'],
                        ['label' => 'E-Liquid', 'url' => '/shop?search=e-liquid'],
                        ['label' => 'Salt E-Liquid', 'url' => '/shop?search=salt%20e-liquid'],
                        ['label' => 'Kratom', 'url' => '/shop?search=kratom'],
                        ['label' => 'Rolling Paper + Filters', 'url' => '/shop?search=rolling%20paper'],
                    ]],
                    ['title' => 'Useful Links', 'links' => [
                        ['label' => 'Home', 'url' => '/'], ['label' => 'About', 'url' => '/about'],
                        ['label' => 'Product Catalog', 'url' => '/shop'], ['label' => 'Blog', 'url' => '/blog'],
                    ]],
                    ['title' => 'Get Started', 'links' => [
                        ['label' => 'My Account', 'url' => '/account/profile'], ['label' => 'Registration', 'url' => '/register'],
                        ['label' => 'Privacy Policy', 'url' => '/privacy-policy'], ['label' => 'Terms and Conditions', 'url' => '/terms-and-conditions'],
                    ]],
                ],
                'business_hours' => [
                    'Monday: 9:00 AM – 7:00 PM', 'Tuesday: 9:00 AM – 7:00 PM',
                    'Wednesday: 9:00 AM – 7:00 PM', 'Thursday: 9:00 AM – 7:00 PM',
                    'Friday: 9:00 AM – 7:00 PM', 'Saturday: 9:00 AM – 7:00 PM', 'Sunday: Closed',
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function down(): void
    {
        Schema::table('homepage_configs', function (Blueprint $table) {
            $table->dropColumn(['site_settings', 'site_logo_path', 'catalog_background_path']);
        });
    }
};
