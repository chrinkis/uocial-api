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

beforeEach(function () {
    $this->withoutMiddleware(UserHasAcceptedLegalDocuments::class);

    Sanctum::actingAs(User::factory()->create());
});

it('filters the departments by any of the given instructors', function () {
    [$alice, $bob, $carol] = Instructor::factory()->count(3)->create();

    $first = Department::factory()->create();
    $second = Department::factory()->create();
    $third = Department::factory()->create();

    CourseOffering::factory()->for(Course::factory()->for($first))->create()->instructors()->attach($alice);
    CourseOffering::factory()->for(Course::factory()->for($second))->create()->instructors()->attach($bob);
    CourseOffering::factory()->for(Course::factory()->for($third))->create()->instructors()->attach($carol);

    $response = $this->getJson('/api/app/departments?'.http_build_query(['instructors' => [$alice->id, $bob->id]]))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect(collect($response->json('data'))->pluck('id')->all())
        ->toEqualCanonicalizing([$first->id, $second->id]);
});

it('does not filter the departments without the parameter', function () {
    Department::factory()->count(3)->create();

    $this->getJson('/api/app/departments')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('rejects unknown instructor ids on the departments', function () {
    $this->getJson('/api/app/departments?instructors[]=999999')
        ->assertUnprocessable();
});

it('filters the counts of a single department without hiding it', function () {
    [$alice, $bob] = Instructor::factory()->count(2)->create();

    $department = Department::factory()->create();
    $taught = Course::factory()->for($department)->create();
    $other = Course::factory()->for($department)->create();

    CourseOffering::factory()->for($taught)->create()->instructors()->attach($alice);
    CourseOffering::factory()->for($other)->create()->instructors()->attach($bob);

    $this->getJson("/api/app/departments/{$department->id}?".http_build_query(['instructors' => [$alice->id]]))
        ->assertOk()
        ->assertJsonPath('data.courses_count', 1)
        ->assertJsonPath('data.instructors_count', 1);

    $nobody = Instructor::factory()->create();

    $this->getJson("/api/app/departments/{$department->id}?".http_build_query(['instructors' => [$nobody->id]]))
        ->assertOk()
        ->assertJsonPath('data.courses_count', 0)
        ->assertJsonPath('data.instructors_count', 0);

    $this->getJson("/api/app/departments/{$department->id}")
        ->assertOk()
        ->assertJsonPath('data.courses_count', 2);

    $this->getJson("/api/app/departments/{$department->id}?instructors[]=999999")
        ->assertUnprocessable();
});

it('searches the departments by code or name', function () {
    $match = Department::factory()->create(['code' => 'CSD', 'name' => 'Computer Science']);
    Department::factory()->create(['code' => 'MATH', 'name' => 'Mathematics']);

    $this->getJson('/api/app/departments?search=computer+sci')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);

    $this->getJson('/api/app/departments?search=csd')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/app/departments?search=%25')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->getJson('/api/app/departments?search=')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
