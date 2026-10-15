<?php

use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\Department;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(UserHasAcceptedLegalDocuments::class);
    Sanctum::actingAs(User::factory()->create());
});

it('lists and shows departments', function () {
    $department = Department::factory()->create(['name' => 'Department of Physics']);

    $this->getJson('/api/app/departments')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Department of Physics');

    $this->getJson("/api/app/departments/{$department->id}")
        ->assertOk()
        ->assertJsonPath('data.code', $department->code);
});

it('creates a department and records who did it', function () {
    $this->postJson('/api/app/departments', [
        'code' => 'csd',
        'name' => 'Department of Computer Science',
        'course_prefix' => 'cs',
    ])->assertCreated()
        ->assertJsonPath('data.code', 'csd');

    expect(Department::count())->toBe(1)
        ->and(Activity::latest('id')->first()->event)->toBe('created');
});

it('validates required fields and rejects codes of existing departments', function () {
    $this->postJson('/api/app/departments', [])->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'name', 'course_prefix']);

    $existing = Department::factory()->create();

    $this->postJson('/api/app/departments', [
        'code' => $existing->code,
        'name' => 'Whatever',
        'course_prefix' => $existing->course_prefix,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'course_prefix']);
});

it('brings back a deleted department when it is created again', function () {
    $department = Department::factory()->create(['code' => 'csd', 'name' => 'Old name']);
    $department->delete();

    $this->postJson('/api/app/departments', [
        'code' => 'csd',
        'name' => 'New name',
        'course_prefix' => $department->course_prefix,
    ])->assertCreated()
        ->assertJsonPath('data.id', $department->id)
        ->assertJsonPath('data.name', 'New name');

    expect(Department::count())->toBe(1)
        ->and(Department::withTrashed()->count())->toBe(1)
        ->and(Activity::latest('id')->limit(2)->pluck('event')->all())->toBe(['updated', 'restored']);
});

it('logs only the restore when the revived department is unchanged', function () {
    $department = Department::factory()->create();
    $department->delete();

    $this->postJson('/api/app/departments', $department->only(['code', 'name', 'course_prefix']))
        ->assertCreated();

    expect(Activity::latest('id')->first()->event)->toBe('restored');
});

it('revives a deleted department that matches only the course prefix', function () {
    $department = Department::factory()->create(['course_prefix' => 'cs']);
    $department->delete();

    $this->postJson('/api/app/departments', [
        'code' => 'brand-new',
        'name' => 'Renamed',
        'course_prefix' => 'cs',
    ])->assertCreated()
        ->assertJsonPath('data.id', $department->id)
        ->assertJsonPath('data.code', 'brand-new');
});

it('refuses to revive when the entry matches two different deleted departments', function () {
    $byCode = Department::factory()->create(['code' => 'csd']);
    $byPrefix = Department::factory()->create(['course_prefix' => 'cs']);
    $byCode->delete();
    $byPrefix->delete();

    $this->postJson('/api/app/departments', [
        'code' => 'csd',
        'name' => 'Whatever',
        'course_prefix' => 'cs',
    ])->assertConflict();

    expect(Department::onlyTrashed()->count())->toBe(2);
});

it('does not let an update reuse the code of a deleted department', function () {
    $deleted = Department::factory()->create();
    $deleted->delete();
    $department = Department::factory()->create();

    $this->patchJson("/api/app/departments/{$department->id}", ['code' => $deleted->code])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('updates a department partially and logs only what changed', function () {
    $department = Department::factory()->create(['name' => 'Old']);

    $this->patchJson("/api/app/departments/{$department->id}", ['name' => 'New'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New');

    $activity = Activity::latest('id')->first();

    expect($activity->event)->toBe('updated')
        ->and($activity->attribute_changes['attributes'])->toBe(['name' => 'New']);
});

it('allows an update that keeps its own unique code', function () {
    $department = Department::factory()->create();

    $this->patchJson("/api/app/departments/{$department->id}", ['code' => $department->code])
        ->assertOk();
});

it('soft deletes a department and hides it afterwards', function () {
    $department = Department::factory()->create();

    $this->deleteJson("/api/app/departments/{$department->id}")->assertNoContent();

    $this->getJson("/api/app/departments/{$department->id}")->assertNotFound();
    expect(Department::withTrashed()->count())->toBe(1)
        ->and(Activity::latest('id')->first()->event)->toBe('deleted');
});

it('shows the history of a department including professor links from the other side', function () {
    $department = Department::factory()->create();
    $other = Department::factory()->create();
    $professor = Professor::factory()->create();

    $department->update(['name' => 'Renamed']);
    $professor->departments()->attach($department);
    $professor->departments()->attach($other);
    $other->update(['name' => 'Unrelated']);

    $response = $this->getJson("/api/app/departments/{$department->id}/activity")->assertOk();

    expect(collect($response->json('data'))->pluck('event')->all())
        ->toBe(['attached', 'updated', 'created']);
    expect($response->json('data.0.meta.relation'))->toBe('departments');
});

it('shows the history of a deleted department', function () {
    $department = Department::factory()->create();
    $department->delete();

    $this->getJson("/api/app/departments/{$department->id}/activity")
        ->assertOk()
        ->assertJsonPath('data.0.event', 'deleted');
});

it('requires authentication', function () {
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/app/departments')->assertUnauthorized();
});
