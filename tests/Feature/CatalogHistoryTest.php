<?php

use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(UserHasAcceptedLegalDocuments::class);
});

function lastActivity(?string $event = null): Activity
{
    return Activity::query()
        ->when($event, fn ($query) => $query->where('event', $event))
        ->latest('id')
        ->firstOrFail();
}

function revert(Activity $activity)
{
    return test()->postJson("/api/app/catalog/activity/{$activity->id}/revert");
}

it('soft deletes catalog models and logs deleted and restored', function () {
    $professor = Professor::factory()->create();

    $professor->delete();
    expect(Professor::count())->toBe(0)
        ->and(lastActivity()->event)->toBe('deleted');

    $professor->restore();
    expect(Professor::count())->toBe(1)
        ->and(lastActivity()->event)->toBe('restored');
});

it('logs attach, detach and sync through the normal relation methods', function () {
    $professor = Professor::factory()->create();
    [$a, $b, $c] = Department::factory()->count(3)->create()->pluck('id')->all();

    $professor->departments()->attach([$a, $b]);
    expect(lastActivity()->event)->toBe('attached')
        ->and(lastActivity()->subject->is($professor))->toBeTrue()
        ->and(lastActivity()->properties['relation'])->toBe('departments')
        ->and(lastActivity()->properties['ids'])->toBe([$a, $b]);

    // Nothing new to attach logs nothing.
    $before = Activity::count();
    $professor->departments()->syncWithoutDetaching([$a]);
    expect(Activity::count())->toBe($before);

    $professor->departments()->sync([$b, $c]);
    expect($professor->departments()->pluck('departments.id')->all())->toEqualCanonicalizing([$b, $c])
        ->and(Activity::where('event', 'attached')->latest('id')->first()->properties['ids'])->toBe([$c])
        ->and(Activity::where('event', 'detached')->latest('id')->first()->properties['ids'])->toBe([$a]);

    // Only ids that were really attached are logged.
    $professor->departments()->detach([$b, 999]);
    expect(lastActivity()->event)->toBe('detached')
        ->and(lastActivity()->properties['ids'])->toBe([$b]);

    $professor->departments()->detach();
    expect(lastActivity()->properties['ids'])->toBe([$c]);
});

it('logs a sync as one attached and one detached entry however many ids it changes', function () {
    $professor = Professor::factory()->create();
    [$a, $b, $c, $d] = Department::factory()->count(4)->create()->pluck('id')->all();
    $professor->departments()->attach($a);
    $before = Activity::count();

    $professor->departments()->sync([$b, $c, $d]);

    expect(Activity::count() - $before)->toBe(2)
        ->and(Activity::where('event', 'attached')->latest('id')->first()->properties['ids'])->toBe([$b, $c, $d])
        ->and(Activity::where('event', 'detached')->latest('id')->first()->properties['ids'])->toBe([$a]);
});

it('logs a pivot change on the model it was made from', function () {
    $professor = Professor::factory()->create();
    $department = Department::factory()->create();

    $department->professors()->attach($professor);

    expect(lastActivity()->subject->is($department))->toBeTrue()
        ->and(lastActivity()->properties['relation'])->toBe('professors');
});

it('does not log a failed duplicate attach', function () {
    $professor = Professor::factory()->create();
    $department = Department::factory()->create();
    $professor->departments()->attach($department);
    $before = Activity::count();

    expect(fn () => $professor->departments()->attach($department))->toThrow(QueryException::class)
        ->and(Activity::count())->toBe($before);
});

it('does not log pivot changes while logging is disabled', function () {
    $before = Activity::count();

    activity()->withoutLogging(fn () => Professor::factory()->create()->departments()->attach(Department::factory()->create()));

    expect(Activity::count())->toBe($before);
});

it('reverts an update and records a reverted entry', function () {
    $admin = User::factory()->admin()->create();
    $professor = Professor::factory()->create(['last_name' => 'Old']);
    $professor->update(['last_name' => 'New']);
    $update = lastActivity('updated');

    Sanctum::actingAs($admin);
    revert($update)->assertCreated()
        ->assertJsonPath('data.event', 'reverted')
        ->assertJsonPath('data.meta.reverted_activity_id', $update->id)
        ->assertJsonPath('data.causer.name', $admin->name);

    expect($professor->fresh()->last_name)->toBe('Old')
        ->and(Activity::where('event', 'updated')->count())->toBe(1);
});

it('reverts an update of a cast attribute', function () {
    $offering = CourseOffering::factory()->create(['year' => 2020]);
    $offering->update(['year' => 2021]);
    $update = lastActivity('updated');

    Sanctum::actingAs(User::factory()->moderator()->create());
    revert($update)->assertCreated();

    expect($offering->fresh()->year)->toBe(2020);
});

it('refuses to revert an update that a later change overwrote', function () {
    $professor = Professor::factory()->create(['last_name' => 'A']);
    $professor->update(['last_name' => 'B']);
    $first = lastActivity('updated');
    $professor->update(['last_name' => 'C']);

    Sanctum::actingAs(User::factory()->moderator()->create());
    revert($first)->assertConflict();

    expect($professor->fresh()->last_name)->toBe('C');
});

it('reverts a creation by deleting and a deletion by restoring', function () {
    Sanctum::actingAs(User::factory()->moderator()->create());
    $department = Department::factory()->create();
    $created = lastActivity('created');

    revert($created)->assertCreated();
    expect(Department::count())->toBe(0);

    $department->restore();
    $department->delete();
    $deleted = lastActivity('deleted');

    revert($deleted)->assertCreated();
    expect(Department::count())->toBe(1);
});

it('reverts attach and detach', function () {
    Sanctum::actingAs(User::factory()->moderator()->create());
    $professor = Professor::factory()->create();
    $department = Department::factory()->create();

    $professor->departments()->attach($department);
    revert(lastActivity('attached'))->assertCreated();
    expect($professor->departments)->toHaveCount(0)
        ->and(Activity::count())->toBe(Activity::whereIn('event', ['created', 'attached', 'reverted'])->count());

    $professor->departments()->attach($department);
    $professor->departments()->detach($department);
    revert(lastActivity('detached'))->assertCreated();
    expect($professor->departments()->count())->toBe(1);
});

it('refuses to revert the same change twice or to revert a revert', function () {
    Sanctum::actingAs(User::factory()->moderator()->create());
    $professor = Professor::factory()->create(['last_name' => 'Old']);
    $professor->update(['last_name' => 'New']);
    $update = lastActivity('updated');

    revert($update)->assertCreated();
    revert($update)->assertConflict();
    revert(lastActivity('reverted'))->assertUnprocessable();
});

it('refuses to revert into a unique constraint violation', function () {
    Sanctum::actingAs(User::factory()->moderator()->create());
    $department = Department::factory()->create(['code' => 'CS']);
    $department->update(['code' => 'CSE']);
    $update = lastActivity('updated');
    Department::factory()->create(['code' => 'CS']);

    // "CS" is taken by another department now.
    revert($update)->assertConflict();
});

it('only lets moderators revert and only catalog entries', function () {
    $professor = Professor::factory()->create(['last_name' => 'Old']);
    $professor->update(['last_name' => 'New']);
    $update = lastActivity('updated');

    Sanctum::actingAs(User::factory()->create());
    revert($update)->assertForbidden();

    Sanctum::actingAs(User::factory()->moderator()->create());
    $other = activity('moderation')->log('something');
    revert($other)->assertNotFound();
});
