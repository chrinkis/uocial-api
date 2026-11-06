<?php

use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(UserHasAcceptedLegalDocuments::class);
});

it('shows a single instructor', function () {
    Sanctum::actingAs(User::factory()->create());

    $instructor = Instructor::factory()->create();

    $this->getJson("/api/app/instructors/{$instructor->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $instructor->id)
        ->assertJsonPath('data.first_name', $instructor->first_name)
        ->assertJsonPath('data.last_name', $instructor->last_name);
});

it('returns not found for an unknown instructor', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/app/instructors/999999')
        ->assertNotFound();
});

it('requires authentication to show an instructor', function () {
    $instructor = Instructor::factory()->create();

    $this->getJson("/api/app/instructors/{$instructor->id}")
        ->assertUnauthorized();
});

it('lists all instructors ordered by name', function () {
    Sanctum::actingAs(User::factory()->create());

    Instructor::factory()->create(['first_name' => 'Zoe', 'last_name' => 'Brown']);
    Instructor::factory()->create(['first_name' => 'Ann', 'last_name' => 'Brown']);
    Instructor::factory()->create(['first_name' => 'Bob', 'last_name' => 'Adams']);

    $this->getJson('/api/app/instructors')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.first_name', 'Bob')
        ->assertJsonPath('data.1.first_name', 'Ann')
        ->assertJsonPath('data.2.first_name', 'Zoe');
});

it('paginates the instructors list', function () {
    Sanctum::actingAs(User::factory()->create());

    Instructor::factory()->count(20)->create();

    $this->getJson('/api/app/instructors')
        ->assertOk()
        ->assertJsonCount(16, 'data')
        ->assertJsonPath('meta.total', 20);
});

it('requires authentication to list instructors', function () {
    $this->getJson('/api/app/instructors')
        ->assertUnauthorized();
});

it('searches the instructors by any name part', function () {
    Sanctum::actingAs(User::factory()->create());

    $match = Instructor::factory()->create(['first_name' => 'Maria', 'middle_name' => 'Eleni', 'last_name' => 'Papadaki']);
    Instructor::factory()->create(['first_name' => 'Nikos', 'middle_name' => null, 'last_name' => 'Papadakis']);
    Instructor::factory()->create(['first_name' => 'Zoe', 'middle_name' => null, 'last_name' => 'Brown']);

    $this->getJson('/api/app/instructors?search=papadak')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/app/instructors?search=maria+papadaki')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);

    $this->getJson('/api/app/instructors?search=eleni')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/app/instructors?search=nobody')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('treats like wildcards in the search literally', function () {
    Sanctum::actingAs(User::factory()->create());

    Instructor::factory()->count(2)->create();

    $this->getJson('/api/app/instructors?search=%25')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('ignores an empty search', function () {
    Sanctum::actingAs(User::factory()->create());

    Instructor::factory()->count(2)->create();

    $this->getJson('/api/app/instructors?search=')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
