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

it('filters the courses by any of the given instructors with filtered counts', function () {
    $department = Department::factory()->create();
    [$alice, $bob, $carol] = Instructor::factory()->count(3)->create();

    $taught = Course::factory()->for($department)->create();
    CourseOffering::factory()->for($taught)->create(['semester' => Semester::Fall])->instructors()->attach([$alice->id, $carol->id]);
    CourseOffering::factory()->for($taught)->create(['semester' => Semester::Spring])->instructors()->attach($bob);
    CourseOffering::factory()->for($taught)->create(['semester' => Semester::Fall])->instructors()->attach($carol);

    $other = Course::factory()->for($department)->create();
    CourseOffering::factory()->for($other)->create()->instructors()->attach($carol);

    $this->getJson("/api/app/departments/{$department->id}/courses?".http_build_query(['instructors' => [$alice->id, $bob->id]]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $taught->id)
        ->assertJsonPath('data.0.offerings_count', 2)
        ->assertJsonPath('data.0.offerings_by_semester.fall', 1)
        ->assertJsonPath('data.0.offerings_by_semester.spring', 1)
        ->assertJsonPath('data.0.instructors_count', 3);
});

it('does not filter the courses without the parameter', function () {
    $department = Department::factory()->create();
    Course::factory()->for($department)->count(2)->create();

    $this->getJson("/api/app/departments/{$department->id}/courses")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('rejects unknown instructor ids on the courses', function () {
    $department = Department::factory()->create();

    $this->getJson("/api/app/departments/{$department->id}/courses?instructors[]=999999")
        ->assertUnprocessable();
});

it('filters the counts of a single course without hiding it', function () {
    [$alice, $bob] = Instructor::factory()->count(2)->create();

    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();

    CourseOffering::factory()->for($course)->create(['semester' => Semester::Fall])->instructors()->attach($alice);
    CourseOffering::factory()->for($course)->create(['semester' => Semester::Spring])->instructors()->attach($bob);

    $this->getJson("/api/app/departments/{$department->id}/courses/{$course->id}?".http_build_query(['instructors' => [$alice->id]]))
        ->assertOk()
        ->assertJsonPath('data.offerings_count', 1)
        ->assertJsonPath('data.offerings_by_semester.fall', 1)
        ->assertJsonPath('data.offerings_by_semester.spring', 0)
        ->assertJsonPath('data.instructors_count', 1);

    $nobody = Instructor::factory()->create();

    $this->getJson("/api/app/departments/{$department->id}/courses/{$course->id}?".http_build_query(['instructors' => [$nobody->id]]))
        ->assertOk()
        ->assertJsonPath('data.offerings_count', 0);

    $this->getJson("/api/app/departments/{$department->id}/courses/{$course->id}")
        ->assertOk()
        ->assertJsonPath('data.offerings_count', 2);
});

it('searches the courses of a department by code or name', function () {
    $department = Department::factory()->create();
    $match = Course::factory()->for($department)->create(['code' => 'CS-101', 'name' => 'Intro to Programming']);
    Course::factory()->for($department)->create(['code' => 'CS-202', 'name' => 'Algorithms']);
    Course::factory()->create(['code' => 'CS-103', 'name' => 'Intro to Programming']);

    $this->getJson("/api/app/departments/{$department->id}/courses?search=intro+program")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);

    $this->getJson("/api/app/departments/{$department->id}/courses?search=cs-")
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson("/api/app/departments/{$department->id}/courses?search=%25")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
