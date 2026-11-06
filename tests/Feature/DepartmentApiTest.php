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
