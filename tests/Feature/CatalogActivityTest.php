<?php

use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\Course;
use App\Models\Department;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(UserHasAcceptedLegalDocuments::class);
});

it('logs catalog changes with the causer and only the dirty attributes', function () {
    $user = User::factory()->create();
    $professor = Professor::factory()->create(['last_name' => 'Old']);

    Sanctum::actingAs($user);
    $professor->update(['last_name' => 'New']);

    $activity = Activity::query()->latest('id')->first();

    expect($activity->log_name)->toBe('catalog')
        ->and($activity->event)->toBe('updated')
        ->and($activity->causer->is($user))->toBeTrue()
        ->and($activity->subject->is($professor))->toBeTrue()
        ->and($activity->attribute_changes['attributes'])->toBe(['last_name' => 'New'])
        ->and($activity->attribute_changes['old'])->toBe(['last_name' => 'Old']);
});

it('does not log a save that changes nothing', function () {
    $department = Department::factory()->create();
    $before = Activity::count();

    $department->save();

    expect(Activity::count())->toBe($before);
});

it('serves the feed newest first', function () {
    Sanctum::actingAs(User::factory()->create());

    $course = Course::factory()->create();
    $course->update(['name' => 'Renamed']);

    $this->getJson('/api/app/catalog/activity')
        ->assertOk()
        ->assertJsonPath('data.0.event', 'updated')
        ->assertJsonPath('data.0.subject.type', 'Course')
        ->assertJsonPath('data.1.event', 'created');
});

it('hides who made a change from everyone but admins', function () {
    $author = User::factory()->create();
    Sanctum::actingAs($author);
    Course::factory()->create();

    foreach ([User::factory()->create(), User::factory()->moderator()->create()] as $viewer) {
        Sanctum::actingAs($viewer);
        $response = $this->getJson('/api/app/catalog/activity')->assertOk();

        expect($response->json('data.0'))->not->toHaveKey('causer')
            ->and($response->getContent())->not->toContain($author->email);
    }

    Sanctum::actingAs(User::factory()->admin()->create());
    $this->getJson('/api/app/catalog/activity')
        ->assertOk()
        ->assertJsonPath('data.0.causer.name', $author->name);
});

it('excludes other logs from the feed', function () {
    Sanctum::actingAs(User::factory()->create());
    activity('moderation')->log('banned someone');

    $this->getJson('/api/app/catalog/activity')
        ->assertOk()
        ->assertJsonMissing(['description' => 'banned someone']);
});

it('requires authentication for the feed', function () {
    $this->getJson('/api/app/catalog/activity')->assertUnauthorized();
});
