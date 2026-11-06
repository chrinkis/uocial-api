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
