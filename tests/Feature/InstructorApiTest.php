<?php

use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Create an instructor with an offering of the given (or a new) course.
 *
 * @param  array<string, mixed>  $attributes
 */
function teacher(array $attributes = [], ?Course $course = null): Instructor
{
    $instructor = Instructor::factory()->create($attributes);

    CourseOffering::factory()
        ->for($course ?? Course::factory()->create())
        ->create()
        ->instructors()
        ->attach($instructor);

    return $instructor;
}

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

it('lists the instructors ordered by name', function () {
    Sanctum::actingAs(User::factory()->create());

    teacher(['first_name' => 'Zoe', 'last_name' => 'Brown']);
    teacher(['first_name' => 'Ann', 'last_name' => 'Brown']);
    teacher(['first_name' => 'Bob', 'last_name' => 'Adams']);

    $this->getJson('/api/app/instructors')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.first_name', 'Bob')
        ->assertJsonPath('data.1.first_name', 'Ann')
        ->assertJsonPath('data.2.first_name', 'Zoe');
});

it('paginates the instructors list', function () {
    Sanctum::actingAs(User::factory()->create());

    foreach (range(1, 20) as $_) {
        teacher();
    }

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

    $match = teacher(['first_name' => 'Maria', 'middle_name' => 'Eleni', 'last_name' => 'Papadaki']);
    teacher(['first_name' => 'Nikos', 'middle_name' => null, 'last_name' => 'Papadakis']);
    teacher(['first_name' => 'Zoe', 'middle_name' => null, 'last_name' => 'Brown']);

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

    teacher();
    teacher();

    $this->getJson('/api/app/instructors?search=%25')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('ignores an empty search', function () {
    Sanctum::actingAs(User::factory()->create());

    teacher();
    teacher();

    $this->getJson('/api/app/instructors?search=')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('lists only the instructors teaching an offering', function () {
    Sanctum::actingAs(User::factory()->create());

    $teaching = teacher();
    Instructor::factory()->create();

    $this->getJson('/api/app/instructors')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $teaching->id);
});

it('searches the instructors of a department', function () {
    Sanctum::actingAs(User::factory()->create());

    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();

    $inside = teacher(['first_name' => 'Maria', 'last_name' => 'Papadaki'], $course);
    teacher(['first_name' => 'Maria', 'last_name' => 'Papadopoulou']);
    teacher(['first_name' => 'Zoe', 'last_name' => 'Brown'], $course);

    $this->getJson("/api/app/departments/{$department->id}/instructors?search=maria")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inside->id);

    $this->getJson("/api/app/departments/{$department->id}/instructors?search=maria+brown")
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->getJson("/api/app/departments/{$department->id}/instructors?search=%25")
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->getJson("/api/app/departments/{$department->id}/instructors?search=")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('searches the instructors of a course', function () {
    Sanctum::actingAs(User::factory()->create());

    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();
    $otherCourse = Course::factory()->for($department)->create();

    $inside = teacher(['first_name' => 'Maria', 'last_name' => 'Papadaki'], $course);
    teacher(['first_name' => 'Maria', 'last_name' => 'Papadopoulou'], $otherCourse);

    $this->getJson("/api/app/departments/{$department->id}/courses/{$course->id}/instructors?search=maria")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inside->id);

    $this->getJson("/api/app/departments/{$department->id}/courses/{$course->id}/instructors?search=nobody")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
