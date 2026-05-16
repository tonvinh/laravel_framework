<?php

use App\Models\Demo;
use App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function (): void {
    $this->user = loginAs();
});

// ── index ──────────────────────────────────────────────────────────────────

it('returns paginated demos', function (): void {
    Demo::factory(5)->create(['user_id' => $this->user->id]);

    getJson('/api/v1/demos')
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount(5, 'data');
});

it('filters demos by status', function (): void {
    Demo::factory(3)->create(['status' => 'published', 'user_id' => $this->user->id]);
    Demo::factory(2)->create(['status' => 'draft', 'user_id' => $this->user->id]);

    getJson('/api/v1/demos?filter[status]=published')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('sorts demos by title', function (): void {
    Demo::factory()->create(['title' => 'Zebra', 'user_id' => $this->user->id]);
    Demo::factory()->create(['title' => 'Alpha', 'user_id' => $this->user->id]);

    $response = getJson('/api/v1/demos?sort=title')->assertOk();

    expect($response->json('data.0.title'))->toBe('Alpha');
});

// ── show ───────────────────────────────────────────────────────────────────

it('shows a single demo', function (): void {
    $demo = Demo::factory()->create(['user_id' => $this->user->id]);

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

it('creates a demo and assigns it to the authenticated user', function (): void {
    postJson('/api/v1/demos', [
        'title'   => 'My Demo',
        'content' => 'Some content here.',
        'status'  => 'published',
    ])->assertCreated()
      ->assertJsonPath('data.title', 'My Demo')
      ->assertJsonPath('data.status', 'published');

    expect(Demo::where('title', 'My Demo')->where('user_id', $this->user->id)->exists())->toBeTrue();
});

// ── update ─────────────────────────────────────────────────────────────────

it('updates a demo owned by the authenticated user', function (): void {
    $demo = Demo::factory()->create(['user_id' => $this->user->id, 'status' => 'draft']);

    putJson("/api/v1/demos/{$demo->uuid}", [
        'title'  => 'Updated Title',
        'status' => 'published',
    ])->assertOk()
      ->assertJsonPath('data.title', 'Updated Title')
      ->assertJsonPath('data.status', 'published');
});

it('forbids updating a demo owned by another user', function (): void {
    $other = User::factory()->create();
    $demo  = Demo::factory()->create(['user_id' => $other->id]);

    putJson("/api/v1/demos/{$demo->uuid}", ['title' => 'Hacked'])
        ->assertForbidden();
});

// ── destroy ────────────────────────────────────────────────────────────────

it('deletes a demo owned by the authenticated user', function (): void {
    $demo = Demo::factory()->create(['user_id' => $this->user->id]);

    deleteJson("/api/v1/demos/{$demo->uuid}")
        ->assertNoContent();

    expect(Demo::find($demo->id))->toBeNull();
    expect(Demo::withTrashed()->find($demo->id))->not->toBeNull();
});

it('forbids deleting a demo owned by another user', function (): void {
    $other = User::factory()->create();
    $demo  = Demo::factory()->create(['user_id' => $other->id]);

    deleteJson("/api/v1/demos/{$demo->uuid}")
        ->assertForbidden();
});

// ── validation ─────────────────────────────────────────────────────────────

it('validates required fields on create', function (string $field): void {
    $data = [
        'title'  => 'Demo Title',
        'status' => 'draft',
    ];

    unset($data[$field]);

    postJson('/api/v1/demos', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);

})->with(['title']);

it('rejects invalid status', function (): void {
    postJson('/api/v1/demos', [
        'title'  => 'Demo Title',
        'status' => 'invalid-status',
    ])->assertUnprocessable()
      ->assertJsonValidationErrors(['status']);
});
