<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomepageConfig;
use App\Models\HomepageSectionItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('returns only active homepage content in configured order', function () {
    $config = HomepageConfig::create(['client_key' => 'new-england', 'label' => 'New England', 'is_active' => true]);
    $catalog = $config->sections()->create(['type' => 'catalog_showcase', 'title' => 'Catalogs', 'sort_order' => 2, 'is_active' => true, 'settings' => ['eyebrow' => 'Distro']]);
    $hero = $config->sections()->create(['type' => 'hero', 'title' => 'Hero', 'sort_order' => 1, 'is_active' => true, 'settings' => []]);
    $config->sections()->create(['type' => 'banner', 'title' => 'Hidden', 'sort_order' => 0, 'is_active' => false, 'settings' => []]);

    $hero->items()->create(['kind' => 'slide', 'title' => 'First', 'desktop_image_path' => 'homepage/new-england/hero.jpg', 'alt_text' => 'Hero', 'sort_order' => 0, 'is_active' => true]);
    $hero->items()->create(['kind' => 'slide', 'title' => 'Hidden', 'desktop_image_path' => 'homepage/new-england/hidden.jpg', 'alt_text' => 'Hidden', 'sort_order' => 1, 'is_active' => false]);
    $catalog->items()->create(['kind' => 'catalog', 'title' => 'Wholesale Catalog', 'desktop_image_path' => 'homepage/new-england/cover.jpg', 'pdf_path' => 'homepage/new-england/catalog.pdf', 'alt_text' => 'Catalog cover', 'sort_order' => 0, 'is_active' => true]);

    $this->getJson('/api/homepage?client=new-england')
        ->assertOk()
        ->assertJsonPath('sections.0.id', $hero->id)
        ->assertJsonPath('sections.1.id', $catalog->id)
        ->assertJsonCount(2, 'sections')
        ->assertJsonCount(1, 'sections.0.items')
        ->assertJsonPath('sections.1.items.0.pdf_url', route('homepage.catalog.pdf', ['item' => $catalog->items()->first()->id]));
});

it('requires an administrator for homepage management', function () {
    $config = HomepageConfig::create(['client_key' => 'client', 'label' => 'Client', 'is_active' => true]);
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get("/admin/homepage/{$config->id}/edit")->assertForbidden();
});

it('uploads a catalog cover and PDF for an administrator', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['email' => 'jay@brainbean.in', 'is_admin' => true]);
    $config = HomepageConfig::create(['client_key' => 'new-england', 'label' => 'New England', 'is_active' => true]);
    $section = $config->sections()->create(['type' => 'catalog_showcase', 'title' => 'Catalogs', 'sort_order' => 0, 'is_active' => true, 'settings' => []]);

    $response = $this->actingAs($admin)->post("/admin/homepage/{$config->id}/sections/{$section->id}/items", [
        'kind' => 'catalog',
        'title' => '2026 Catalog',
        'alt_text' => '2026 catalog cover',
        'is_active' => true,
        'desktop_image' => UploadedFile::fake()->image('cover.jpg', 420, 594),
        'pdf_file' => UploadedFile::fake()->createWithContent('catalog.pdf', '%PDF-1.4 test'),
    ]);

    $response->assertRedirect();
    $item = HomepageSectionItem::firstOrFail();
    Storage::disk('public')->assertExists($item->desktop_image_path);
    Storage::disk('public')->assertExists($item->pdf_path);
    $this->get(route('homepage.catalog.pdf', ['item' => $item->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('stores uploaded site branding and removes legacy URL fallbacks', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $config = HomepageConfig::create([
        'client_key' => 'new-england',
        'label' => 'New England',
        'is_active' => true,
        'site_logo_path' => 'homepage/new-england/site/old-logo.png',
        'catalog_background_path' => 'homepage/new-england/site/old-background.png',
        'site_settings' => [
            'site_name' => 'New England Distribution',
            'logo_url' => '/images/brand/old-logo.png',
            'catalog_background_logo_url' => '/images/brand/old-background.png',
        ],
    ]);
    Storage::disk('public')->put($config->site_logo_path, 'old logo');
    Storage::disk('public')->put($config->catalog_background_path, 'old background');

    $this->actingAs($admin)->patch("/admin/homepage/{$config->id}", [
        'client_key' => 'new-england',
        'label' => 'New England',
        'is_active' => true,
        'site_settings' => $config->site_settings,
        'site_logo' => UploadedFile::fake()->image('logo.png', 400, 400),
        'catalog_background_image' => UploadedFile::fake()->image('background.png', 1200, 800),
    ])->assertRedirect();

    $config->refresh();

    expect($config->site_settings['logo_url'])->toBeNull()
        ->and($config->site_settings['catalog_background_logo_url'])->toBeNull()
        ->and($config->site_logo_path)->not->toBe('homepage/new-england/site/old-logo.png')
        ->and($config->catalog_background_path)->not->toBe('homepage/new-england/site/old-background.png');
    Storage::disk('public')->assertExists($config->site_logo_path);
    Storage::disk('public')->assertExists($config->catalog_background_path);
    Storage::disk('public')->assertMissing('homepage/new-england/site/old-logo.png');
    Storage::disk('public')->assertMissing('homepage/new-england/site/old-background.png');
});

it('removes stored branding safely and keeps valid external fallbacks', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $config = HomepageConfig::create([
        'client_key' => 'new-england',
        'label' => 'New England',
        'is_active' => true,
        'site_logo_path' => 'homepage/new-england/site/logo.png',
        'catalog_background_path' => 'homepage/new-england/site/background.png',
        'site_settings' => ['site_name' => 'New England Distribution'],
    ]);
    Storage::disk('public')->put($config->site_logo_path, 'logo');
    Storage::disk('public')->put($config->catalog_background_path, 'background');

    $this->actingAs($admin)->patch("/admin/homepage/{$config->id}", [
        'client_key' => 'new-england',
        'label' => 'New England',
        'is_active' => true,
        'site_settings' => [
            'site_name' => 'New England Distribution',
            'logo_url' => 'https://cdn.example.com/logo.png',
            'catalog_background_logo_url' => 'https://cdn.example.com/background.png',
        ],
        'remove_site_logo' => true,
        'remove_catalog_background' => true,
    ])->assertRedirect();

    $config->refresh();

    expect($config->site_logo_path)->toBeNull()
        ->and($config->catalog_background_path)->toBeNull()
        ->and($config->site_settings['logo_url'])->toBe('https://cdn.example.com/logo.png')
        ->and($config->site_settings['catalog_background_logo_url'])->toBe('https://cdn.example.com/background.png');
    Storage::disk('public')->assertMissing('homepage/new-england/site/logo.png');
    Storage::disk('public')->assertMissing('homepage/new-england/site/background.png');
    $this->getJson('/api/homepage?client=new-england')
        ->assertOk()
        ->assertJsonPath('site.logo_url', 'https://cdn.example.com/logo.png')
        ->assertJsonPath('site.catalog_background_logo_url', 'https://cdn.example.com/background.png');
});

it('keeps the homepage client key immutable after creation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $config = HomepageConfig::create([
        'client_key' => 'new-england',
        'label' => 'New England',
        'is_active' => true,
        'site_settings' => [],
    ]);

    $this->actingAs($admin)->patch("/admin/homepage/{$config->id}", [
        'client_key' => 'changed-client',
        'label' => 'New England',
        'is_active' => true,
        'site_settings' => [],
    ])->assertSessionHasErrors('client_key');

    expect($config->fresh()->client_key)->toBe('new-england');
});

it('accepts a secure external image URL instead of an uploaded image', function () {
    Storage::fake('public');
    Http::fake(['*' => Http::response('fake-image-content', 200, ['Content-Type' => 'image/webp'])]);
    $admin = User::factory()->create(['is_admin' => true]);
    $config = HomepageConfig::create(['client_key' => 'new-england', 'label' => 'New England', 'is_active' => true]);
    $section = $config->sections()->create(['type' => 'banner', 'title' => 'Promotions', 'sort_order' => 0, 'is_active' => true, 'settings' => []]);

    $this->actingAs($admin)->post("/admin/homepage/{$config->id}/sections/{$section->id}/items", [
        'kind' => 'content',
        'title' => 'Remote banner',
        'alt_text' => 'Remote promotional banner',
        'desktop_image_external_url' => 'https://example.com/banner.webp',
        'mobile_image_external_url' => 'https://example.com/banner-mobile.webp',
        'is_active' => true,
    ])->assertRedirect();

    $item = HomepageSectionItem::firstOrFail();
    expect($item->desktop_image_path)->not->toBeNull()
        ->and($item->desktop_image_external_url)->toBe('https://example.com/banner.webp');
    Storage::disk('public')->assertExists($item->desktop_image_path);

    $this->getJson('/api/homepage?client=new-england')
        ->assertOk()
        ->assertJsonPath('sections.0.items.0.desktop_image_url', url(Storage::disk('public')->url($item->desktop_image_path)))
        ->assertJsonPath('sections.0.items.0.mobile_image_url', url(Storage::disk('public')->url($item->mobile_image_path)));
});

it('rejects nested homepage items from another section', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $first = HomepageConfig::create(['client_key' => 'first', 'label' => 'First', 'is_active' => true]);
    $second = HomepageConfig::create(['client_key' => 'second', 'label' => 'Second', 'is_active' => true]);
    $firstSection = $first->sections()->create(['type' => 'banner', 'title' => 'First', 'sort_order' => 0, 'is_active' => true, 'settings' => []]);
    $secondSection = $second->sections()->create(['type' => 'banner', 'title' => 'Second', 'sort_order' => 0, 'is_active' => true, 'settings' => []]);
    $item = $secondSection->items()->create(['kind' => 'content', 'desktop_image_path' => 'homepage/second/banner.jpg', 'alt_text' => 'Banner', 'sort_order' => 0, 'is_active' => true]);

    $this->actingAs($admin)->delete("/admin/homepage/{$first->id}/sections/{$firstSection->id}/items/{$item->id}")->assertNotFound();
    expect($item->fresh())->not->toBeNull();
});

it('normalizes multipart site settings into the shape the storefront reads', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $config = HomepageConfig::create(['client_key' => 'new-england', 'label' => 'New England', 'is_active' => true, 'site_settings' => ['newsletter_enabled' => true]]);

    // The edit page sends multipart data: booleans become "0"/"1" and empty arrays are omitted.
    $this->actingAs($admin)->post("/admin/homepage/{$config->id}", [
        '_method' => 'patch',
        'client_key' => 'new-england',
        'label' => 'New England',
        'is_active' => '1',
        'site_settings' => [
            'newsletter_enabled' => '0',
            'nav_groups' => [['label' => 'Sale', 'url' => '/sale']],
            'footer_columns' => [['title' => 'Coming soon']],
        ],
    ])->assertSessionHasNoErrors();

    expect($config->fresh()->site_settings)
        ->newsletter_enabled->toBeFalse()
        ->nav_groups->toBe([['label' => 'Sale', 'url' => '/sale', 'keywords' => []]])
        ->footer_columns->toBe([['title' => 'Coming soon', 'links' => []]])
        ->business_hours->toBe([]);
    $this->getJson('/api/homepage?client=new-england')->assertJsonPath('site.newsletter_enabled', false);
});

it('keeps featured category and brand selections on homepage sections', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::create(['name' => 'Vapes', 'slug' => 'vapes', 'is_active' => true]);
    $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
    $config = HomepageConfig::create(['client_key' => 'new-england', 'label' => 'New England', 'is_active' => true]);

    $this->actingAs($admin)->post("/admin/homepage/{$config->id}/sections", [
        'type' => 'featured_category', 'title' => 'Featured', 'is_active' => true,
        'settings' => ['category_id' => $category->id, 'product_limit' => 4],
    ])->assertSessionHasNoErrors();
    $this->actingAs($admin)->post("/admin/homepage/{$config->id}/sections", [
        'type' => 'brand_showcase', 'title' => 'Brands', 'is_active' => true,
        'settings' => ['brand_ids' => [$brand->id]],
    ])->assertSessionHasNoErrors();

    [$featured, $brands] = $config->sections()->orderBy('sort_order')->get();
    expect($featured->settings)->toBe(['category_id' => $category->id, 'product_limit' => 4])
        ->and($brands->settings)->toBe(['brand_ids' => [$brand->id]]);
    $this->getJson('/api/homepage?client=new-england')
        ->assertJsonPath('sections.0.data.category.id', $category->id)
        ->assertJsonPath('sections.1.data.brands.0.id', $brand->id);
});

it('adds a Kratom product carousel between existing sections and serves its products', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $config = HomepageConfig::create(['client_key' => 'new-england', 'label' => 'New England', 'is_active' => true]);
    $cigar = $config->sections()->create(['type' => 'product_carousel', 'title' => 'Top Cigar', 'sort_order' => 0, 'is_active' => true, 'settings' => ['limit' => 14, 'product_ids' => []]]);
    $hydroxy = $config->sections()->create(['type' => 'product_carousel', 'title' => '7-Hydroxymitragynine', 'sort_order' => 1, 'is_active' => true, 'settings' => ['limit' => 14, 'product_ids' => []]]);
    $brands = $config->sections()->create(['type' => 'brand_showcase', 'title' => 'Top Brands', 'sort_order' => 2, 'is_active' => true, 'settings' => ['brand_ids' => []]]);
    $kratom = Category::create(['name' => 'Kratom', 'slug' => 'kratom', 'is_active' => true]);
    $product = fn (string $name, array $extra = []) => Product::create($extra + ['name' => $name, 'category_id' => $kratom->id, 'regular_price' => 10, 'is_active' => true, 'in_stock' => true]);
    [$a, $b, $c] = [$product('Kratom A'), $product('Kratom B'), $product('Kratom C')];
    $inactive = $product('Kratom retired', ['is_active' => false]);
    $variant = $product('Kratom A - 10ct', ['parent_id' => $a->id]);

    // Created through the same admin endpoint and product rules as every product carousel...
    $this->actingAs($admin)->post("/admin/homepage/{$config->id}/sections", [
        'type' => 'product_carousel', 'title' => 'Kratom', 'is_active' => true,
        'settings' => ['limit' => 14, 'product_ids' => [$b->id, $a->id, $inactive->id, $variant->id, $c->id]],
    ])->assertSessionHasNoErrors();
    $section = $config->sections()->where('title', 'Kratom')->firstOrFail();
    expect($section->sort_order)->toBe(3)                                       // a new section is added last
        ->and($section->settings)->toBe(['limit' => 14, 'product_ids' => [$b->id, $a->id, $inactive->id, $variant->id, $c->id]]);

    // ...and placed after Top Cigar with the existing ordering endpoint, keeping the other sections in their order.
    $this->actingAs($admin)->post("/admin/homepage/{$config->id}/sections/reorder", ['sections' => [
        ['id' => $cigar->id, 'sort_order' => 0], ['id' => $section->id, 'sort_order' => 1],
        ['id' => $hydroxy->id, 'sort_order' => 2], ['id' => $brands->id, 'sort_order' => 3],
    ]])->assertSessionHasNoErrors();

    $this->getJson('/api/homepage?client=new-england')->assertOk()
        ->assertJsonPath('sections.*.title', ['Top Cigar', 'Kratom', '7-Hydroxymitragynine', 'Top Brands'])
        ->assertJsonPath('sections.1.type', 'product_carousel')
        // Selected order kept; inactive products and variants are not shown.
        ->assertJsonPath('sections.1.data.product_ids', [$b->id, $a->id, $c->id]);
    $this->getJson('/api/products?ids='.implode(',', [$b->id, $a->id, $c->id]))->assertOk()
        ->assertJsonCount(3, 'data');
});

it('saves Kratom carousel edits with the product carousel validation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $config = HomepageConfig::create(['client_key' => 'new-england', 'label' => 'New England', 'is_active' => true]);
    $section = $config->sections()->create(['type' => 'product_carousel', 'title' => 'Kratom', 'sort_order' => 0, 'is_active' => true, 'settings' => ['limit' => 14, 'product_ids' => []]]);
    [$a, $b] = [Product::create(['name' => 'Kratom A', 'regular_price' => 10, 'is_active' => true]), Product::create(['name' => 'Kratom B', 'regular_price' => 10, 'is_active' => true])];
    $url = "/admin/homepage/{$config->id}/sections/{$section->id}";
    $save = fn (array $settings, bool $active = true) => $this->actingAs($admin)->patch($url, ['type' => 'product_carousel', 'title' => 'Kratom', 'is_active' => $active, 'settings' => $settings]);

    $save(['limit' => 1, 'product_ids' => [$b->id, $a->id]])->assertSessionHasNoErrors();
    $this->getJson('/api/homepage?client=new-england')->assertJsonPath('sections.0.data.product_ids', [$b->id]);   // limit applies

    $save(['limit' => 14, 'product_ids' => [999999]])->assertSessionHasErrors('settings.product_ids.0');
    $save(['limit' => 14, 'product_ids' => [$a->id, $a->id]])->assertSessionHasErrors('settings.product_ids.0');
    $save(['limit' => 51, 'product_ids' => [$a->id]])->assertSessionHasErrors('settings.limit');
    expect($section->fresh()->settings['product_ids'])->toBe([$b->id, $a->id]);

    $save(['limit' => 14, 'product_ids' => [$a->id]], active: false)->assertSessionHasNoErrors();
    $this->getJson('/api/homepage?client=new-england')->assertJsonCount(0, 'sections');
});

it('rejects a remote image URL that redirects to a private address', function () {
    Storage::fake('public');
    Http::fake([
        'https://example.com/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/internal.png']),
        'http://127.0.0.1/*' => Http::response('internal', 200, ['Content-Type' => 'image/png']),
    ]);
    $admin = User::factory()->create(['is_admin' => true]);
    $config = HomepageConfig::create(['client_key' => 'new-england', 'label' => 'New England', 'is_active' => true]);
    $section = $config->sections()->create(['type' => 'banner', 'title' => 'Promotions', 'sort_order' => 0, 'is_active' => true, 'settings' => []]);

    $this->actingAs($admin)->post("/admin/homepage/{$config->id}/sections/{$section->id}/items", [
        'kind' => 'content',
        'desktop_image_external_url' => 'https://example.com/banner.png',
        'is_active' => true,
    ])->assertSessionHasErrors(['desktop_image_external_url' => 'The image URL must point to a publicly accessible server.']);

    expect(HomepageSectionItem::count())->toBe(0);
    Http::assertNotSent(fn ($request) => str_starts_with($request->url(), 'http://127.0.0.1'));
});

it('follows a remote image redirect between public addresses', function () {
    Storage::fake('public');
    Http::fake([
        'https://example.com/old.png' => Http::response('', 301, ['Location' => 'https://example.com/new.png']),
        'https://example.com/new.png' => Http::response('image', 200, ['Content-Type' => 'image/png']),
    ]);
    $admin = User::factory()->create(['is_admin' => true]);
    $config = HomepageConfig::create(['client_key' => 'new-england', 'label' => 'New England', 'is_active' => true]);
    $section = $config->sections()->create(['type' => 'banner', 'title' => 'Promotions', 'sort_order' => 0, 'is_active' => true, 'settings' => []]);

    $this->actingAs($admin)->post("/admin/homepage/{$config->id}/sections/{$section->id}/items", [
        'kind' => 'content',
        'desktop_image_external_url' => 'https://example.com/old.png',
        'is_active' => true,
    ])->assertSessionHasNoErrors();

    Storage::disk('public')->assertExists(HomepageSectionItem::firstOrFail()->desktop_image_path);
});

it('reports the promotional banner limit on product sections as a validation error', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $config = HomepageConfig::create(['client_key' => 'new-england', 'label' => 'New England', 'is_active' => true]);
    $section = $config->sections()->create(['type' => 'product_carousel', 'title' => 'Deals', 'sort_order' => 0, 'is_active' => true, 'settings' => ['product_ids' => []]]);
    foreach ([0, 1] as $order) {
        $section->items()->create(['kind' => 'content', 'desktop_image_path' => "homepage/new-england/banner-{$order}.jpg", 'sort_order' => $order, 'is_active' => true]);
    }

    $this->actingAs($admin)->post("/admin/homepage/{$config->id}/sections/{$section->id}/items", [
        'kind' => 'content',
        'desktop_image' => UploadedFile::fake()->image('third.jpg'),
        'is_active' => true,
    ])->assertSessionHasErrors(['kind' => 'A product section supports at most two promotional banners.']);

    expect($section->items()->count())->toBe(2);
});
