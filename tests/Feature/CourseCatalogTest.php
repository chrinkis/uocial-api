<?php

use App\Enums\Semester;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Professor;
use Database\Seeders\CourseOfferingSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\ProfessorSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('links departments, courses and offerings', function () {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();
    $offering = CourseOffering::factory()->for($course)->create([
        'semester' => Semester::Fall,
        'year' => 2026,
    ]);

    expect($department->courses)->toHaveCount(1)
        ->and($course->department->is($department))->toBeTrue()
        ->and($course->offerings->first()?->is($offering))->toBeTrue()
        ->and($offering->semester)->toBe(Semester::Fall);
});

it('links professors to departments and offerings many-to-many', function () {
    $professor = Professor::factory()->create();
    $departments = Department::factory()->count(2)->create();
    $offering = CourseOffering::factory()->create();

    $professor->departments()->attach($departments);
    $professor->courseOfferings()->attach($offering);

    expect($professor->departments)->toHaveCount(2)
        ->and($departments->first()->professors)->toHaveCount(1)
        ->and($offering->professors->first()?->is($professor))->toBeTrue();
});

it('builds the full name without a missing middle name', function () {
    $professor = Professor::factory()->create([
        'first_name' => 'Ada',
        'middle_name' => null,
        'last_name' => 'Lovelace',
    ]);

    expect($professor->full_name)->toBe('Ada Lovelace');

    $professor->middle_name = 'King';

    expect($professor->full_name)->toBe('Ada King Lovelace');
});

it('rejects a duplicate course code within a department', function () {
    $department = Department::factory()->create();
    Course::factory()->for($department)->create(['code' => '101']);

    Course::factory()->for($department)->create(['code' => '101']);
})->throws(QueryException::class);

it('allows the same course code in different departments', function () {
    Course::factory()->create(['code' => '101']);
    Course::factory()->create(['code' => '101']);

    expect(Course::query()->where('code', '101')->count())->toBe(2);
});

it('rejects a duplicate offering for the same course, semester and year', function () {
    $course = Course::factory()->create();
    $attributes = ['semester' => Semester::Fall, 'year' => 2026];

    CourseOffering::factory()->for($course)->create($attributes);
    CourseOffering::factory()->for($course)->create($attributes);
})->throws(QueryException::class);

it('rejects duplicate pivot pairs', function () {
    $professor = Professor::factory()->create();
    $department = Department::factory()->create();

    $professor->departments()->attach($department);
    $professor->departments()->attach($department);
})->throws(QueryException::class);

it('seeds a consistent course catalog', function () {
    $this->seed([
        DepartmentSeeder::class,
        CourseSeeder::class,
        ProfessorSeeder::class,
        CourseOfferingSeeder::class,
    ]);

    expect(Department::query()->count())->toBe(6)
        ->and(Course::query()->count())->toBe(72)
        ->and(Professor::query()->count())->toBe(40)
        ->and(CourseOffering::query()->count())->toBeGreaterThanOrEqual(72 * 3)
        ->and(Professor::query()->doesntHave('departments')->count())->toBe(0);
});
