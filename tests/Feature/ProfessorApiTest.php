<?php

use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\CourseOffering;
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

function eventsOf(string $uri): array
{
    return collect(test()->getJson($uri)->assertOk()->json('data'))->pluck('event')->all();
}

it('lists professors with their departments and filters by department', function () {
    $department = Department::factory()->create();
    $professor = Professor::factory()->create();
    $professor->departments()->attach($department);
    Professor::factory()->create();

    $this->getJson('/api/app/professors')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson("/api/app/professors?department_id={$department->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $professor->id)
        ->assertJsonPath('data.0.departments.0.code', $department->code);
});

it('shows a professor with departments and course offering ids', function () {
    $professor = Professor::factory()->create(['first_name' => 'Ada', 'middle_name' => null, 'last_name' => 'Lovelace']);
    $department = Department::factory()->create();
    $offering = CourseOffering::factory()->create();
    $professor->departments()->attach($department);
    $professor->courseOfferings()->attach($offering);

    $this->getJson("/api/app/professors/{$professor->id}")
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Ada Lovelace')
        ->assertJsonPath('data.departments.0.id', $department->id)
        ->assertJsonPath('data.course_offering_ids', [$offering->id]);
});

it('creates a professor with departments and offerings and logs every change', function () {
    $departments = Department::factory()->count(2)->create();
    $offering = CourseOffering::factory()->create();

    $response = $this->postJson('/api/app/professors', [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'department_ids' => $departments->pluck('id')->all(),
        'course_offering_ids' => [$offering->id],
    ])->assertCreated()
        ->assertJsonCount(2, 'data.departments')
        ->assertJsonPath('data.course_offering_ids', [$offering->id]);

    $professor = Professor::findOrFail($response->json('data.id'));

    expect($professor->departments)->toHaveCount(2)
        ->and(Activity::where('subject_type', Professor::class)->pluck('event')->all())
        ->toBe(['created', 'attached', 'attached']);
});

it('creates a professor without relations and allows duplicate names', function () {
    $payload = ['first_name' => 'Ada', 'last_name' => 'Lovelace'];

    $this->postJson('/api/app/professors', $payload)->assertCreated();
    $this->postJson('/api/app/professors', $payload)->assertCreated();

    expect(Professor::count())->toBe(2);
});

it('validates names and related ids', function () {
    $this->postJson('/api/app/professors', [])->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name', 'last_name']);

    $deleted = Department::factory()->create();
    $deleted->delete();
    $department = Department::factory()->create();

    $this->postJson('/api/app/professors', [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'department_ids' => [999, $deleted->id, $department->id, $department->id],
        'course_offering_ids' => [999],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors([
            'department_ids.0',
            'department_ids.1',
            'department_ids.3',
            'course_offering_ids.0',
        ]);

    expect(Professor::count())->toBe(0);
});

it('updates names only and leaves relations alone', function () {
    $professor = Professor::factory()->create(['last_name' => 'Old']);
    $professor->departments()->attach(Department::factory()->create());

    $this->patchJson("/api/app/professors/{$professor->id}", ['last_name' => 'New'])
        ->assertOk()
        ->assertJsonPath('data.last_name', 'New')
        ->assertJsonCount(1, 'data.departments');

    expect(Activity::latest('id')->first()->event)->toBe('updated');
});

it('replaces departments, logging what was attached and detached', function () {
    [$a, $b, $c] = Department::factory()->count(3)->create()->all();
    $professor = Professor::factory()->create();
    $professor->departments()->attach([$a->id, $b->id]);

    $this->patchJson("/api/app/professors/{$professor->id}", ['department_ids' => [$b->id, $c->id]])
        ->assertOk();

    expect($professor->departments()->pluck('departments.id')->all())->toEqualCanonicalizing([$b->id, $c->id])
        ->and(Activity::where('event', 'detached')->latest('id')->first()->properties['ids'])->toBe([$a->id])
        ->and(Activity::where('event', 'attached')->latest('id')->first()->properties['ids'])->toBe([$c->id]);
});

it('clears departments with an empty list', function () {
    $professor = Professor::factory()->create();
    $professor->departments()->attach(Department::factory()->create());

    $this->patchJson("/api/app/professors/{$professor->id}", ['department_ids' => []])
        ->assertOk()
        ->assertJsonCount(0, 'data.departments');
});

it('soft deletes a professor and hides it afterwards', function () {
    $professor = Professor::factory()->create();

    $this->deleteJson("/api/app/professors/{$professor->id}")->assertNoContent();

    $this->getJson("/api/app/professors/{$professor->id}")->assertNotFound();
    expect(Professor::withTrashed()->count())->toBe(1)
        ->and(Activity::latest('id')->first()->event)->toBe('deleted');
});

it('shows the history of a professor including links made from the other sides', function () {
    $professor = Professor::factory()->create();
    $other = Professor::factory()->create();
    $department = Department::factory()->create();
    $offering = CourseOffering::factory()->create();

    $professor->update(['last_name' => 'Renamed']);
    $department->professors()->attach($professor);
    $offering->professors()->attach($professor);
    $department->professors()->attach($other);
    $other->update(['last_name' => 'Unrelated']);

    expect(eventsOf("/api/app/professors/{$professor->id}/activity"))
        ->toBe(['attached', 'attached', 'updated', 'created']);
});

it('shows the history of a deleted professor', function () {
    $professor = Professor::factory()->create();
    $professor->delete();

    $this->getJson("/api/app/professors/{$professor->id}/activity")
        ->assertOk()
        ->assertJsonPath('data.0.event', 'deleted');
});

it('requires authentication', function () {
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/app/professors')->assertUnauthorized();
});
