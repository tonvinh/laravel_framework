<?php

use App\Models\Demo;
use App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function (): void {
    loginAs();
});

// ── index ──────────────────────────────────────────────────────────────────

it('returns paginated demos', function (): void {
    Demo::factory(5)->create();

    getJson('/api/v1/demos')
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount(5, 'data');
});

it('filters demos by status', function (): void {
    Demo::factory(3)->create(['status' => 'published']);
    Demo::factory(2)->create(['status' => 'draft']);

    getJson('/api/v1/demos?filter[status]=published')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('sorts demos by title', function (): void {
    Demo::factory()->create(['title' => 'Zebra']);
    Demo::factory()->create(['title' => 'Alpha']);

    $response = getJson('/api/v1/demos?sort=title')->assertOk();

    expect($response->json('data.0.title'))->toBe('Alpha');
});

// ── show ───────────────────────────────────────────────────────────────────

it('shows a single demo', function (): void {
    $demo = Demo::factory()->create();

    getJson("/api/v1/demos/{$demo->uuid}")
        ->assertOk()
        ->assertJsonPath('data.uuid', $demo->uuid)
        ->assertJsonPath('data.title', $demo->title);
});

it('returns 404 for unknown uuid', function (): void {
    getJson('/api/v1/demos/non-existent-uuid')
        ->assertNotFound();
});

// ── store ──────────────────────────────────────────────────────────────────

it('creates a demo', function (): void {
    $user = User::factory()->create();

    postJson('/api/v1/demos', [
        'title'   => 'My Demo',
        'content' => 'Some content here.',
        'status'  => 'published',
        'user_id' => $user->id,
    ])->assertCreated()
      ->assertJsonPath('data.title', 'My Demo')
      ->assertJsonPath('data.status', 'published');

    expect(Demo::where('title', 'My Demo')->exists())->toBeTrue();
});

// ── update ─────────────────────────────────────────────────────────────────

it('updates a demo', function (): void {
    $demo = Demo::factory()->create(['status' => 'draft']);

    putJson("/api/v1/demos/{$demo->uuid}", [
        'title'   => 'Updated Title',
        'status'  => 'published',
        'user_id' => $demo->user_id,
    ])->assertOk()
      ->assertJsonPath('data.title', 'Updated Title')
      ->assertJsonPath('data.status', 'published');
});

// ── destroy ────────────────────────────────────────────────────────────────

it('deletes a demo', function (): void {
    $demo = Demo::factory()->create();

    deleteJson("/api/v1/demos/{$demo->uuid}")
        ->assertNoContent();

    expect(Demo::find($demo->id))->toBeNull();
    expect(Demo::withTrashed()->find($demo->id))->not->toBeNull();
});

// ── validation ─────────────────────────────────────────────────────────────

it('validates required fields on create', function (string $field): void {
    $data = [
        'title'   => 'Demo Title',
        'status'  => 'draft',
        'user_id' => User::factory()->create()->id,
    ];

    unset($data[$field]);

    postJson('/api/v1/demos', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);

})->with(['title', 'user_id']);

it('rejects invalid status', function (): void {
    postJson('/api/v1/demos', [
        'title'   => 'Demo Title',
        'status'  => 'invalid-status',
        'user_id' => User::factory()->create()->id,
    ])->assertUnprocessable()
      ->assertJsonValidationErrors(['status']);
});
