<?php

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper to create an approved user
function approvedUser(): User
{
    return User::factory()->create(['approval_status' => 'approved']);
}

// Helper: a valid address payload
function addressPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Jane',
        'last_name'  => 'Doe',
        'address_1'  => '123 Main Street',
        'city'       => 'Springfield',
        'postcode'   => '62701',
        'country'    => 'US',
    ], $overrides);
}

// --- Authentication guard ---

test('unauthenticated requests to index return 401', function () {
    $this->getJson('/api/addresses')->assertUnauthorized();
});

test('unauthenticated requests to store return 401', function () {
    $this->postJson('/api/addresses', addressPayload())->assertUnauthorized();
});

// --- Index ---

test('authenticated user can list their addresses', function () {
    $user = approvedUser();

    $this->actingAs($user, 'api')
        ->getJson('/api/addresses')
        ->assertOk()
        ->assertJsonStructure(['data'])
        ->assertJson(['data' => []]);
});

test('index only returns the authenticated users addresses', function () {
    $user  = approvedUser();
    $other = approvedUser();

    Address::create(array_merge(addressPayload(), ['user_id' => $other->id, 'is_default' => true]));

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/addresses')
        ->assertOk();

    expect($response->json('data'))->toBeEmpty();
});

test('index returns default address first', function () {
    $user = approvedUser();

    $second  = Address::create(array_merge(addressPayload(['address_1' => 'Second St']), ['user_id' => $user->id, 'is_default' => false]));
    $default = Address::create(array_merge(addressPayload(['address_1' => 'First St']), ['user_id' => $user->id, 'is_default' => true]));

    $response = $this->actingAs($user, 'api')
        ->getJson('/api/addresses')
        ->assertOk();

    expect($response->json('data.0.id'))->toBe($default->id);
});

// --- Store ---

test('user can create an address', function () {
    $user = approvedUser();

    $this->actingAs($user, 'api')
        ->postJson('/api/addresses', addressPayload(['label' => 'Home']))
        ->assertCreated()
        ->assertJsonStructure(['message', 'data' => [
            'id', 'label', 'first_name', 'last_name', 'company',
            'address_1', 'address_2', 'city', 'state', 'postcode',
            'country', 'phone', 'is_default',
        ]])
        ->assertJson(['data' => ['label' => 'Home', 'first_name' => 'Jane']]);
});

test('first address created is automatically set as default', function () {
    $user = approvedUser();

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/addresses', addressPayload())
        ->assertCreated();

    expect($response->json('data.is_default'))->toBeTrue();
});

test('second address without is_default does not steal default', function () {
    $user    = approvedUser();
    $first   = Address::create(array_merge(addressPayload(), ['user_id' => $user->id, 'is_default' => true]));

    $this->actingAs($user, 'api')
        ->postJson('/api/addresses', addressPayload(['address_1' => '456 Oak Ave']))
        ->assertCreated()
        ->assertJson(['data' => ['is_default' => false]]);

    expect($first->fresh()->is_default)->toBeTrue();
});

test('creating with is_default true clears other defaults', function () {
    $user  = approvedUser();
    $first = Address::create(array_merge(addressPayload(), ['user_id' => $user->id, 'is_default' => true]));

    $this->actingAs($user, 'api')
        ->postJson('/api/addresses', addressPayload(['address_1' => '456 Oak Ave', 'is_default' => true]))
        ->assertCreated()
        ->assertJson(['data' => ['is_default' => true]]);

    expect($first->fresh()->is_default)->toBeFalse();
});

test('store returns 422 when required fields are missing', function () {
    $user = approvedUser();

    $this->actingAs($user, 'api')
        ->postJson('/api/addresses', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name', 'last_name', 'address_1', 'city', 'postcode', 'country']);
});

test('store returns 422 for invalid country code length', function () {
    $user = approvedUser();

    $this->actingAs($user, 'api')
        ->postJson('/api/addresses', addressPayload(['country' => 'USA']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['country']);
});

// --- Show ---

test('user can fetch their own address', function () {
    $user    = approvedUser();
    $address = Address::create(array_merge(addressPayload(), ['user_id' => $user->id, 'is_default' => true]));

    $this->actingAs($user, 'api')
        ->getJson("/api/addresses/{$address->id}")
        ->assertOk()
        ->assertJson(['id' => $address->id, 'first_name' => 'Jane']);
});

test('fetching another users address returns 404', function () {
    $user    = approvedUser();
    $other   = approvedUser();
    $address = Address::create(array_merge(addressPayload(), ['user_id' => $other->id, 'is_default' => true]));

    $this->actingAs($user, 'api')
        ->getJson("/api/addresses/{$address->id}")
        ->assertNotFound();
});

// --- Update ---

test('user can update their own address', function () {
    $user    = approvedUser();
    $address = Address::create(array_merge(addressPayload(), ['user_id' => $user->id, 'is_default' => true]));

    $this->actingAs($user, 'api')
        ->patchJson("/api/addresses/{$address->id}", ['city' => 'Chicago'])
        ->assertOk()
        ->assertJson(['data' => ['city' => 'Chicago']]);
});

test('updating with is_default true clears other defaults', function () {
    $user   = approvedUser();
    $first  = Address::create(array_merge(addressPayload(), ['user_id' => $user->id, 'is_default' => true]));
    $second = Address::create(array_merge(addressPayload(['address_1' => '456 Oak Ave']), ['user_id' => $user->id, 'is_default' => false]));

    $this->actingAs($user, 'api')
        ->patchJson("/api/addresses/{$second->id}", ['is_default' => true])
        ->assertOk()
        ->assertJson(['data' => ['is_default' => true]]);

    expect($first->fresh()->is_default)->toBeFalse();
});

test('updating another users address returns 404', function () {
    $user    = approvedUser();
    $other   = approvedUser();
    $address = Address::create(array_merge(addressPayload(), ['user_id' => $other->id, 'is_default' => true]));

    $this->actingAs($user, 'api')
        ->patchJson("/api/addresses/{$address->id}", ['city' => 'Hack City'])
        ->assertNotFound();
});

// --- Destroy ---

test('user can delete their own address', function () {
    $user    = approvedUser();
    $address = Address::create(array_merge(addressPayload(), ['user_id' => $user->id, 'is_default' => true]));

    $this->actingAs($user, 'api')
        ->deleteJson("/api/addresses/{$address->id}")
        ->assertOk()
        ->assertJson(['message' => 'Address deleted']);

    expect(Address::find($address->id))->toBeNull();
});

test('deleting another users address returns 404', function () {
    $user    = approvedUser();
    $other   = approvedUser();
    $address = Address::create(array_merge(addressPayload(), ['user_id' => $other->id, 'is_default' => true]));

    $this->actingAs($user, 'api')
        ->deleteJson("/api/addresses/{$address->id}")
        ->assertNotFound();

    expect(Address::find($address->id))->not->toBeNull();
});
