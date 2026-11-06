<?php

use App\Enums\Semester;
use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(UserHasAcceptedLegalDocuments::class);

    Sanctum::actingAs(User::factory()->create());
});

function courseOfferingUrl(Department $department, Course $course, ?CourseOffering $offering = null): string
{
    $url = "/api/app/departments/{$department->id}/courses/{$course->id}/offerings";

    return $offering === null ? $url : "{$url}/{$offering->id}";
}

it('lists only the offerings of the course ordered by most recent term', function () {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();

    $fall2024 = CourseOffering::factory()->for($course)->create(['year' => 2024, 'semester' => Semester::Fall]);
    $fall2025 = CourseOffering::factory()->for($course)->create(['year' => 2025, 'semester' => Semester::Fall]);
    $spring2025 = CourseOffering::factory()->for($course)->create(['year' => 2025, 'semester' => Semester::Spring]);

    $instructor = Instructor::factory()->create();
    $spring2025->instructors()->attach($instructor);

    CourseOffering::factory()->create();

    $this->getJson(courseOfferingUrl($department, $course))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $spring2025->id)
        ->assertJsonPath('data.1.id', $fall2025->id)
        ->assertJsonPath('data.2.id', $fall2024->id)
        ->assertJsonPath('data.0.instructors.0.id', $instructor->id);
});

it('shows an offering with its instructors', function () {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();
    $offering = CourseOffering::factory()->for($course)->create();

    $offering->instructors()->attach(Instructor::factory()->count(2)->create());

    $this->getJson(courseOfferingUrl($department, $course, $offering))
        ->assertOk()
        ->assertJsonPath('data.id', $offering->id)
        ->assertJsonPath('data.course_id', $course->id)
        ->assertJsonCount(2, 'data.instructors');
});

it('does not show an offering that belongs to another course', function () {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();
    $otherOffering = CourseOffering::factory()->create();

    $this->getJson(courseOfferingUrl($department, $course, $otherOffering))
        ->assertNotFound();
});

it('does not list offerings of a course that belongs to another department', function () {
    $department = Department::factory()->create();
    $otherCourse = Course::factory()->create();

    $this->getJson(courseOfferingUrl($department, $otherCourse))
        ->assertNotFound();
});

it('filters the offerings by any of the given instructors', function () {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();
    [$alice, $bob, $carol] = Instructor::factory()->count(3)->create();

    $aliceOffering = CourseOffering::factory()->for($course)->create();
    $bothOffering = CourseOffering::factory()->for($course)->create();
    $carolOffering = CourseOffering::factory()->for($course)->create();
    $aliceOffering->instructors()->attach($alice);
    $bothOffering->instructors()->attach([$alice->id, $bob->id]);
    $carolOffering->instructors()->attach($carol);

    $this->getJson(courseOfferingUrl($department, $course).'?'.http_build_query(['instructors' => [$alice->id, $bob->id]]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $aliceOffering->id])
        ->assertJsonFragment(['id' => $bothOffering->id]);

    $this->getJson(courseOfferingUrl($department, $course))
        ->assertJsonCount(3, 'data');
});

it('rejects an invalid instructors filter on the offerings', function (mixed $value) {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();

    $this->getJson(courseOfferingUrl($department, $course).'?'.http_build_query(['instructors' => $value]))
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('instructors');
})->with([
    'not an array' => 'abc',
]);

it('rejects unknown instructor ids on the offerings', function () {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();

    $this->getJson(courseOfferingUrl($department, $course).'?instructors[]=999999')
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('instructors.0');
});

it('searches the offerings of the course by year or semester', function () {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();

    $match = CourseOffering::factory()->for($course)->create(['year' => 2024, 'semester' => Semester::Fall]);
    CourseOffering::factory()->for($course)->create(['year' => 2025, 'semester' => Semester::Fall]);
    CourseOffering::factory()->for($course)->create(['year' => 2025, 'semester' => Semester::Spring]);
    CourseOffering::factory()->create(['year' => 2024, 'semester' => Semester::Fall]);

    $this->getJson(courseOfferingUrl($department, $course).'?search=2024+fall')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);

    $this->getJson(courseOfferingUrl($department, $course).'?search=2025')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson(courseOfferingUrl($department, $course).'?search=spring')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
