<?php

use App\Models\HomepageConfig;
use App\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

function contentConfig(array $site = []): HomepageConfig
{
    return HomepageConfig::create([
        'client_key' => 'new-england',
        'label' => 'New England',
        'is_active' => true,
        'site_settings' => $site,
    ]);
}

function siteUpdate(HomepageConfig $config, array $site): array
{
    return [
        'client_key' => $config->client_key,
        'label' => $config->label,
        'is_active' => true,
        'site_settings' => $site,
    ];
}

// --- GET /api/homepage ---------------------------------------------------------------------------------------

it('always returns the content keys, null when the admin has not set them', function () {
    $config = contentConfig(['site_name' => 'Store']);
    $config->sections()->create(['type' => 'product_carousel', 'title' => 'Top', 'sort_order' => 0, 'is_active' => true, 'settings' => ['product_ids' => []]]);
    $config->sections()->create(['type' => 'hero', 'title' => 'Hero', 'sort_order' => 1, 'is_active' => true, 'settings' => []]);

    $response = $this->getJson('/api/homepage?client=new-england')->assertOk();

    expect($response->json('site'))->toHaveKeys(['nicotine_warning', 'app_store_url', 'play_store_url'])
        ->and($response->json('site.nicotine_warning'))->toBeNull()
        ->and($response->json('site.site_name'))->toBe('Store')
        ->and($response->json('sections.0.settings'))->toHaveKey('badge')
        ->and($response->json('sections.0.settings.badge'))->toBeNull()
        ->and($response->json('sections.1.settings'))->not->toHaveKey('badge');
});

it('returns the values the admin saved', function () {
    $config = contentConfig([
        'nicotine_warning' => 'WARNING: This product contains nicotine.',
        'app_store_url' => 'https://apps.apple.com/us/app/x/id1',
        'play_store_url' => 'https://play.google.com/store/apps/details?id=x',
    ]);
    $config->sections()->create(['type' => 'product_carousel', 'title' => 'New Arrivals', 'sort_order' => 0, 'is_active' => true, 'settings' => ['product_ids' => [], 'badge' => 'New']]);

    $this->getJson('/api/homepage?client=new-england')
        ->assertOk()
        ->assertJsonPath('site.nicotine_warning', 'WARNING: This product contains nicotine.')
        ->assertJsonPath('site.app_store_url', 'https://apps.apple.com/us/app/x/id1')
        ->assertJsonPath('site.play_store_url', 'https://play.google.com/store/apps/details?id=x')
        ->assertJsonPath('sections.0.settings.badge', 'New');
});

// --- Admin validation -----------------------------------------------------------------------------------------

it('saves the warning, store links and section badge for an administrator', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $config = contentConfig();
    $section = $config->sections()->create(['type' => 'product_carousel', 'title' => 'New Arrivals', 'sort_order' => 0, 'is_active' => true, 'settings' => ['product_ids' => []]]);

    $this->actingAs($admin)->patch("/admin/homepage/{$config->id}", siteUpdate($config, [
        'nicotine_warning' => 'WARNING: This product contains nicotine.',
        'app_store_url' => 'https://apps.apple.com/us/app/x/id1',
        'play_store_url' => 'https://play.google.com/store/apps/details?id=x',
    ]))->assertSessionHasNoErrors();

    $this->actingAs($admin)->patch("/admin/homepage/{$config->id}/sections/{$section->id}", [
        'type' => 'product_carousel',
        'title' => 'New Arrivals',
        'is_active' => true,
        'settings' => ['limit' => 14, 'product_ids' => [], 'badge' => 'New'],
    ])->assertSessionHasNoErrors();

    expect($config->fresh()->site_settings)->toMatchArray([
        'nicotine_warning' => 'WARNING: This product contains nicotine.',
        'app_store_url' => 'https://apps.apple.com/us/app/x/id1',
        'play_store_url' => 'https://play.google.com/store/apps/details?id=x',
    ])->and($section->fresh()->settings['badge'])->toBe('New');
});

it('rejects store links that are not https links on the store host', function (string $key, string $url) {
    $admin = User::factory()->create(['is_admin' => true]);
    $config = contentConfig();

    $this->actingAs($admin)->patch("/admin/homepage/{$config->id}", siteUpdate($config, [$key => $url]))
        ->assertSessionHasErrors("site_settings.{$key}");
})->with([
    ['app_store_url', 'http://apps.apple.com/us/app/x/id1'],
    ['app_store_url', 'https://apps.apple.com.evil.example/app'],
    ['app_store_url', 'https://user:pass@apps.apple.com/app'],
    ['app_store_url', 'javascript:alert(1)'],
    ['play_store_url', 'https://play.google.com:8443/store'],
    ['play_store_url', 'https://evil.example/?u=play.google.com'],
]);

it('limits the warning and badge length', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $config = contentConfig();
    $section = $config->sections()->create(['type' => 'product_carousel', 'title' => 'Top', 'sort_order' => 0, 'is_active' => true, 'settings' => ['product_ids' => []]]);

    $this->actingAs($admin)->patch("/admin/homepage/{$config->id}", siteUpdate($config, ['nicotine_warning' => str_repeat('a', 301)]))
        ->assertSessionHasErrors('site_settings.nicotine_warning');
    $this->actingAs($admin)->patch("/admin/homepage/{$config->id}/sections/{$section->id}", [
        'type' => 'product_carousel', 'title' => 'Top', 'is_active' => true, 'settings' => ['limit' => 14, 'product_ids' => [], 'badge' => str_repeat('b', 21)],
    ])->assertSessionHasErrors('settings.badge');
});

// --- Data migration -------------------------------------------------------------------------------------------

it('moves the storefront values into the New England config without overwriting admin values', function () {
    $migration = require database_path('migrations/2026_10_26_000002_add_storefront_content_settings_to_new_england.php');
    $config = contentConfig(['play_store_url' => 'https://play.google.com/store/apps/details?id=custom']);
    $arrivals = $config->sections()->create(['type' => 'product_carousel', 'title' => 'New Arrivals', 'sort_order' => 0, 'is_active' => true, 'settings' => ['product_ids' => []]]);
    $other = $config->sections()->create(['type' => 'product_carousel', 'title' => 'Top Cigar', 'sort_order' => 1, 'is_active' => true, 'settings' => ['product_ids' => []]]);
    $otherClient = HomepageConfig::create(['client_key' => 'other', 'label' => 'Other', 'is_active' => true, 'site_settings' => []]);

    $migration->up();

    expect($config->fresh()->site_settings)->toMatchArray([
        'nicotine_warning' => 'WARNING: This product contains nicotine. Nicotine is an addictive chemical.',
        'app_store_url' => 'https://apps.apple.com/in/app/new-england-distribution/id6741199522',
        'play_store_url' => 'https://play.google.com/store/apps/details?id=custom',
    ])->and($arrivals->fresh()->settings['badge'])->toBe('New')
        ->and($other->fresh()->settings)->not->toHaveKey('badge')
        ->and($otherClient->fresh()->site_settings)->toBe([]);

    $migration->down();

    expect($config->fresh()->site_settings)->toBe(['play_store_url' => 'https://play.google.com/store/apps/details?id=custom'])
        ->and($arrivals->fresh()->settings)->not->toHaveKey('badge');
});

// --- POST /api/newsletter -------------------------------------------------------------------------------------

it('stores a newsletter sign-up once and answers the same for a repeat', function () {
    $config = contentConfig(['newsletter_enabled' => true]);

    $this->postJson('/api/newsletter', ['client' => 'new-england', 'email' => 'Buyer@Example.com'])
        ->assertOk()->assertExactJson(['message' => 'Thanks for subscribing.']);
    $this->postJson('/api/newsletter', ['client' => 'new-england', 'email' => 'buyer@example.com'])
        ->assertOk()->assertExactJson(['message' => 'Thanks for subscribing.']);

    expect(NewsletterSubscription::where('homepage_config_id', $config->id)->pluck('email')->all())->toBe(['buyer@example.com']);
});

it('accepts the seeded string flag for an enabled newsletter', function () {
    contentConfig(['newsletter_enabled' => '1']);

    $this->postJson('/api/newsletter', ['client' => 'new-england', 'email' => 'a@example.com'])->assertOk();
    expect(NewsletterSubscription::count())->toBe(1);
});

it('refuses sign-ups when the newsletter is off or the client is unknown', function () {
    contentConfig(['newsletter_enabled' => false]);

    $this->postJson('/api/newsletter', ['client' => 'new-england', 'email' => 'a@example.com'])->assertNotFound();
    $this->postJson('/api/newsletter', ['client' => 'missing', 'email' => 'a@example.com'])->assertNotFound();
    expect(NewsletterSubscription::count())->toBe(0);
});

it('validates the e-mail address', function () {
    contentConfig(['newsletter_enabled' => true]);

    $this->postJson('/api/newsletter', ['client' => 'new-england', 'email' => 'not-an-email'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('silently drops honeypot submissions', function () {
    contentConfig(['newsletter_enabled' => true]);

    $this->postJson('/api/newsletter', ['client' => 'new-england', 'email' => 'bot@example.com', 'website' => 'http://spam.example'])
        ->assertOk();
    expect(NewsletterSubscription::count())->toBe(0);
});

it('rate-limits newsletter sign-ups per IP', function () {
    contentConfig(['newsletter_enabled' => true]);
    RateLimiter::clear('api-newsletter');

    foreach (range(1, 5) as $i) {
        $this->postJson('/api/newsletter', ['client' => 'new-england', 'email' => "u{$i}@example.com"])->assertOk();
    }
    $this->postJson('/api/newsletter', ['client' => 'new-england', 'email' => 'u6@example.com'])->assertStatus(429);
});

it('exports sign-ups as CSV for administrators only', function () {
    $config = contentConfig(['newsletter_enabled' => true]);
    $config->newsletterSubscriptions()->create(['email' => 'buyer@example.com']);
    $config->newsletterSubscriptions()->create(['email' => '=cmd@example.com']);

    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->get("/admin/homepage/{$config->id}/newsletter-subscriptions.csv")->assertForbidden();

    $csv = $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get("/admin/homepage/{$config->id}/newsletter-subscriptions.csv")
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain("email,subscribed_at\n")
        ->toContain('buyer@example.com')
        ->toContain("'=cmd@example.com");
});

it('removes sign-ups together with their homepage config', function () {
    $config = contentConfig(['newsletter_enabled' => true]);
    $config->newsletterSubscriptions()->create(['email' => 'buyer@example.com']);

    DB::statement('PRAGMA foreign_keys = ON');
    $config->delete();

    expect(NewsletterSubscription::count())->toBe(0);
});
