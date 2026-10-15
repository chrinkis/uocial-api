<?php

use App\Enums\Semester;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\PrivacyPolicy;
use App\Models\Professor;
use App\Models\TermsOfUse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function signInForCatalog(User $user): void
{
    $privacyPolicy = PrivacyPolicy::latest('id')->first() ?? PrivacyPolicy::factory()->create();
    $termsOfUse = TermsOfUse::latest('id')->first() ?? TermsOfUse::factory()->create();

    $user->acceptedPrivacyPolicies()->attach($privacyPolicy->id);
    $user->acceptedTermsOfUses()->attach($termsOfUse->id);

    Sanctum::actingAs($user);
}

it('lets users read the catalog', function () {
    signInForCatalog(User::factory()->create());

    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();
    $offering = CourseOffering::factory()->for($course)->create();
    $professor = Professor::factory()->create();
    $professor->departments()->attach($department);

    $this->getJson('/api/app/departments')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/app/departments/{$department->id}")->assertOk()->assertJsonPath('data.code', $department->code);
    $this->getJson("/api/app/departments/{$department->id}/courses")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/app/departments/{$department->id}/courses/{$course->id}")->assertOk()->assertJsonPath('data.department.id', $department->id);
    $this->getJson("/api/app/departments/{$department->id}/courses/{$course->id}/offerings")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/app/departments/{$department->id}/courses/{$course->id}/offerings/{$offering->id}")->assertOk();
    $this->getJson("/api/app/departments/{$department->id}/professors")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/app/departments/{$department->id}/professors/{$professor->id}")->assertOk()->assertJsonPath('data.full_name', $professor->full_name);
});

it('does not resolve children through the wrong parent', function () {
    signInForCatalog(User::factory()->create());

    $department = Department::factory()->create();
    $course = Course::factory()->create();
    $professor = Professor::factory()->create();

    $this->getJson("/api/app/departments/{$department->id}/courses/{$course->id}")->assertNotFound();
    $this->getJson("/api/app/departments/{$department->id}/professors/{$professor->id}")->assertNotFound();
});

it('forbids users from changing the catalog', function () {
    signInForCatalog(User::factory()->create());

    $department = Department::factory()->create();

    $this->postJson('/api/app/departments', ['code' => 'x', 'name' => 'X', 'course_prefix' => 'x'])->assertForbidden();
    $this->patchJson("/api/app/departments/{$department->id}", ['name' => 'Y'])->assertForbidden();
    $this->deleteJson("/api/app/departments/{$department->id}")->assertForbidden();
    $this->postJson("/api/app/departments/{$department->id}/courses", ['code' => '1', 'name' => 'X'])->assertForbidden();

    expect($department->fresh()->name)->toBe($department->name);
});

it('lets moderators manage departments', function () {
    signInForCatalog(User::factory()->moderator()->create());

    $id = $this->postJson('/api/app/departments', [
        'code' => 'csd',
        'name' => 'Computer Science',
        'course_prefix' => 'cs',
    ])->assertCreated()->json('data.id');

    $this->postJson('/api/app/departments', [
        'code' => 'csd',
        'name' => 'Duplicate',
        'course_prefix' => 'dup',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');

    $this->patchJson("/api/app/departments/{$id}", ['code' => 'csd', 'name' => 'CS'])
        ->assertOk()
        ->assertJsonPath('data.name', 'CS');

    $this->deleteJson("/api/app/departments/{$id}")->assertOk();

    expect(Department::find($id))->toBeNull()
        ->and(Department::withTrashed()->find($id))->not->toBeNull();

    $this->getJson("/api/app/departments/{$id}")->assertNotFound();
});

it('lets moderators manage courses', function () {
    signInForCatalog(User::factory()->moderator()->create());

    $department = Department::factory()->create();
    $url = "/api/app/departments/{$department->id}/courses";

    $id = $this->postJson($url, ['code' => '101', 'name' => 'Intro'])
        ->assertCreated()
        ->json('data.id');

    expect(Course::find($id)->department->is($department))->toBeTrue();

    $this->postJson($url, ['code' => '101', 'name' => 'Again'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    $this->patchJson("{$url}/{$id}", ['code' => '101', 'name' => 'Intro 2'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Intro 2');

    $this->deleteJson("{$url}/{$id}")->assertOk();

    expect(Course::withTrashed()->find($id)->trashed())->toBeTrue();
});

it('lets moderators manage professors', function () {
    signInForCatalog(User::factory()->moderator()->create());

    [$department, $other] = Department::factory()->count(2)->create();
    $url = "/api/app/departments/{$department->id}/professors";

    $id = $this->postJson($url, [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'department_ids' => [$other->id],
    ])->assertCreated()->json('data.id');

    expect(Professor::find($id)->departments->pluck('id')->all())
        ->toEqualCanonicalizing([$department->id, $other->id]);

    $this->patchJson("{$url}/{$id}", ['last_name' => 'King', 'department_ids' => [$department->id]])
        ->assertOk()
        ->assertJsonPath('data.last_name', 'King');

    expect(Professor::find($id)->departments)->toHaveCount(1);

    $this->deleteJson("{$url}/{$id}")->assertOk();

    expect(Professor::find($id))->toBeNull();
});

it('lets moderators manage course offerings', function () {
    signInForCatalog(User::factory()->moderator()->create());

    $course = Course::factory()->create();
    $professor = Professor::factory()->create();
    $url = "/api/app/departments/{$course->department_id}/courses/{$course->id}/offerings";

    $payload = [
        'semester' => 'Fall',
        'year' => 2026,
        'professor_ids' => [$professor->id],
    ];

    $id = $this->postJson($url, $payload)
        ->assertCreated()
        ->assertJsonPath('data.semester', 'Fall')
        ->json('data.id');

    expect(CourseOffering::find($id)->professors)->toHaveCount(1);

    $this->postJson($url, $payload)->assertUnprocessable();

    $other = CourseOffering::factory()->for($course)->create(['semester' => Semester::Spring, 'year' => 2026]);

    $this->patchJson("{$url}/{$other->id}", ['semester' => 'Fall'])->assertUnprocessable();
    $this->patchJson("{$url}/{$other->id}", ['year' => 2025])->assertOk();

    $this->deleteJson("{$url}/{$id}")->assertOk();

    expect(CourseOffering::find($id))->toBeNull();
});
