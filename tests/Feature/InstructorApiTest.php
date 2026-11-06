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
