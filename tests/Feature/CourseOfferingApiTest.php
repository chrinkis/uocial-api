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
