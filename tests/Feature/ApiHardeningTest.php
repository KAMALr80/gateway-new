<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function hardeningOrderPayload(User $user, array $items, array $extra = []): array
{
    $payload = ['items' => $items, 'billing_email' => $user->email];
    foreach (['first_name' => 'Jane', 'last_name' => 'Doe', 'address_1' => '1 Main St', 'city' => 'Town', 'postcode' => '12345', 'country' => 'US', 'phone' => '5550000'] as $k => $v) {
        $payload["billing_{$k}"]  = $v;
        $payload["shipping_{$k}"] = $v;
    }

    return array_merge($payload, $extra);
}

test('order totals ignore discount, shipping, tax and currency sent by the client', function () {
    Queue::fake();
    $user    = User::factory()->create(['approval_status' => 'approved']);
    $product = Product::create(['name' => 'Priced', 'sku' => 'PRICED-1', 'regular_price' => '25.00', 'is_active' => true, 'in_stock' => true]);

    $this->actingAs($user, 'api')->postJson('/api/orders', hardeningOrderPayload($user, [
        ['product_id' => $product->id, 'quantity' => 2],
    ], ['discount_total' => 49.99, 'shipping_total' => 15, 'total_tax' => 0, 'currency' => 'EUR']))->assertCreated();

    $order = Order::firstOrFail();
    expect((float) $order->line_total)->toBe(50.0)
        ->and((float) $order->discount_total)->toBe(0.0)
        ->and((float) $order->total)->toBe(50.0)
        ->and($order->currency)->toBe('USD');
});

test('order quantities and duplicate lines are validated', function () {
    $user    = User::factory()->create(['approval_status' => 'approved']);
    $product = Product::create(['name' => 'Q', 'sku' => 'Q-1', 'regular_price' => '1.00', 'is_active' => true, 'in_stock' => true]);

    $this->actingAs($user, 'api')->postJson('/api/orders', hardeningOrderPayload($user, [
        ['product_id' => $product->id, 'quantity' => 100000],
    ]))->assertStatus(422)->assertJsonValidationErrors('items.0.quantity');

    $this->actingAs($user, 'api')->postJson('/api/orders', hardeningOrderPayload($user, [
        ['product_id' => $product->id, 'quantity' => 1],
        ['product_id' => $product->id, 'quantity' => 1],
    ]))->assertStatus(422);
});

test('login is rate limited per account and IP', function () {
    $user = User::factory()->create(['approval_status' => 'approved']);

    foreach (range(1, 5) as $_) {
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertStatus(401);
    }

    $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertStatus(429);
});

test('password reset e-mails are rate limited', function () {
    foreach (range(1, 5) as $_) {
        $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk();
    }

    $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com'])->assertStatus(429);
});

test('internal sync endpoints reject a missing or wrong key', function () {
    config(['services.erp.internal_key' => 'correct-key']);

    $this->postJson('/internal/brands/sync', [])->assertUnauthorized();
    $this->postJson('/internal/brands/sync', [], ['X-Internal-Key' => 'wrong-key'])->assertUnauthorized();
});

test('internal sync endpoints stay closed when no key is configured', function () {
    config(['services.erp.internal_key' => null]);

    $this->postJson('/internal/brands/sync', [], ['X-Internal-Key' => ''])->assertUnauthorized();
});

test('users cannot update another users profile or escalate their role', function () {
    [$user, $other] = [User::factory()->create(['approval_status' => 'pending']), User::factory()->create()];

    $this->actingAs($user, 'api')->patchJson("/api/users/{$other->id}", ['name' => 'Hacked'])->assertForbidden();

    $this->actingAs($user, 'api')->patchJson("/api/users/{$user->id}", [
        'name' => 'Me', 'is_admin' => true, 'approval_status' => 'approved',
    ])->assertOk();

    $user->refresh();
    expect($user->name)->toBe('Me')
        ->and((bool) $user->is_admin)->toBeFalse()
        ->and($user->approval_status)->not->toBe('approved');
});

test('non numeric ids are a 404, not a server error', function () {
    $this->getJson('/api/products/abc')->assertNotFound();
    $this->getJson('/api/categories/abc')->assertNotFound();
    $this->getJson('/api/brands/abc')->assertNotFound();
});

test('an order is still confirmed when the ERP sync cannot be queued', function () {
    $user    = User::factory()->create(['approval_status' => 'approved']);
    $product = Product::create(['name' => 'Erp', 'sku' => 'ERP-1', 'regular_price' => '3.00', 'is_active' => true, 'in_stock' => true]);
    $this->mock(\Illuminate\Contracts\Bus\Dispatcher::class)
        ->shouldReceive('dispatch')->andThrow(new RuntimeException('queue down'));

    $this->actingAs($user, 'api')->postJson('/api/orders', hardeningOrderPayload($user, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]))->assertCreated();

    expect(Order::count())->toBe(1);
});
