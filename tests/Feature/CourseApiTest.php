<?php

use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(UserHasAcceptedLegalDocuments::class);
    Sanctum::actingAs(User::factory()->create());
});

it('lists courses with their department and filters by department', function () {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();
    Course::factory()->create();

    $this->getJson('/api/app/courses')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson("/api/app/courses?department_id={$department->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $course->id)
        ->assertJsonPath('data.0.department.code', $department->code);

    $this->getJson("/api/app/courses/{$course->id}")
        ->assertOk()
        ->assertJsonPath('data.code', $course->code);
});

it('creates a course and logs it', function () {
    $department = Department::factory()->create();

    $this->postJson('/api/app/courses', [
        'department_id' => $department->id,
        'code' => '101',
        'name' => 'Intro',
    ])->assertCreated()
        ->assertJsonPath('data.department.id', $department->id);

    expect(Activity::latest('id')->first()->event)->toBe('created');
});

it('validates required fields and the department', function () {
    $this->postJson('/api/app/courses', [])->assertUnprocessable()
        ->assertJsonValidationErrors(['department_id', 'code', 'name']);

    $deleted = Department::factory()->create();
    $deleted->delete();

    foreach ([999, $deleted->id] as $departmentId) {
        $this->postJson('/api/app/courses', [
            'department_id' => $departmentId,
            'code' => '101',
            'name' => 'Intro',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('department_id');
    }
});

it('keeps codes unique per department', function () {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create(['code' => '101']);

    $this->postJson('/api/app/courses', [
        'department_id' => $department->id,
        'code' => '101',
        'name' => 'Duplicate',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    // The same code is fine in another department.
    $this->postJson('/api/app/courses', [
        'department_id' => Department::factory()->create()->id,
        'code' => '101',
        'name' => 'Other department',
    ])->assertCreated();

    // Keeping its own code is fine.
    $this->patchJson("/api/app/courses/{$course->id}", ['code' => '101'])->assertOk();
});

it('brings back a deleted course when it is created again', function () {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create(['code' => '101', 'name' => 'Old name']);
    $course->delete();

    $this->postJson('/api/app/courses', [
        'department_id' => $department->id,
        'code' => '101',
        'name' => 'New name',
    ])->assertCreated()
        ->assertJsonPath('data.id', $course->id)
        ->assertJsonPath('data.name', 'New name');

    expect(Course::withTrashed()->count())->toBe(1)
        ->and(Activity::latest('id')->limit(2)->pluck('event')->all())->toBe(['updated', 'restored']);
});

it('does not let an update reuse the code of a deleted course', function () {
    $department = Department::factory()->create();
    $deleted = Course::factory()->for($department)->create(['code' => '102']);
    $deleted->delete();
    $course = Course::factory()->for($department)->create(['code' => '101']);

    $this->patchJson("/api/app/courses/{$course->id}", ['code' => '102'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('checks uniqueness against the new department when only the department changes', function () {
    $from = Department::factory()->create();
    $to = Department::factory()->create();
    $course = Course::factory()->for($from)->create(['code' => '101']);
    Course::factory()->for($to)->create(['code' => '101']);

    $this->patchJson("/api/app/courses/{$course->id}", ['department_id' => $to->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('updates a course partially and logs only what changed', function () {
    $course = Course::factory()->create(['name' => 'Old']);

    $this->patchJson("/api/app/courses/{$course->id}", ['name' => 'New'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New');

    $activity = Activity::latest('id')->first();

    expect($activity->event)->toBe('updated')
        ->and($activity->attribute_changes['attributes'])->toBe(['name' => 'New']);
});

it('soft deletes a course and hides it afterwards', function () {
    $course = Course::factory()->create();

    $this->deleteJson("/api/app/courses/{$course->id}")->assertNoContent();

    $this->getJson("/api/app/courses/{$course->id}")->assertNotFound();
    expect(Course::withTrashed()->count())->toBe(1)
        ->and(Activity::latest('id')->first()->event)->toBe('deleted');
});

it('shows the history of a course, deleted ones included', function () {
    $course = Course::factory()->create();
    $other = Course::factory()->create();
    $course->update(['name' => 'Renamed']);
    $other->update(['name' => 'Unrelated']);
    $course->delete();

    $response = $this->getJson("/api/app/courses/{$course->id}/activity")->assertOk();

    expect(collect($response->json('data'))->pluck('event')->all())
        ->toBe(['deleted', 'updated', 'created']);
});

it('requires authentication', function () {
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/app/courses')->assertUnauthorized();
});
