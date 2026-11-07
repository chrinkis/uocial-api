<?php

use App\Enums\DataReportReason;
use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\Course;
use App\Models\CourseReport;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\CourseReportSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(UserHasAcceptedLegalDocuments::class);

    $this->user = User::factory()->create();

    Sanctum::actingAs($this->user);
});

function courseReportUrl(Course $course): string
{
    return "/api/app/departments/{$course->department_id}/courses/{$course->id}/reports";
}

it('reports a course', function (string $reason) {
    $course = Course::factory()->create();

    $this->postJson(courseReportUrl($course), [
        'reason' => $reason,
        'comment' => 'The name is wrong',
    ])
        ->assertCreated()
        ->assertJsonPath('data.reason', $reason)
        ->assertJsonPath('data.course.id', $course->id)
        ->assertJsonPath('data.resolved_at', null);

    $this->assertDatabaseHas('course_reports', [
        'user_id' => $this->user->id,
        'course_id' => $course->id,
        'department_id' => null,
        'reason' => $reason,
    ]);
})->with(['false_data', 'other']);

it('does not report a course through another department', function () {
    $course = Course::factory()->create();
    $department = Department::factory()->create();

    $this->postJson("/api/app/departments/{$department->id}/courses/{$course->id}/reports", [
        'reason' => 'other',
        'comment' => 'Wrong department',
    ])->assertNotFound();
});

it('reports a duplicate course', function () {
    [$course, $original] = Course::factory()->count(2)->create();

    $this->postJson(courseReportUrl($course), [
        'reason' => 'duplicate',
        'duplicate_of_id' => $original->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.duplicate_of.id', $original->id)
        ->assertJsonPath('data.comment', null);
});

it('validates the duplicate reference', function () {
    $course = Course::factory()->create();

    $this->postJson(courseReportUrl($course), ['reason' => 'duplicate'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');

    $this->postJson(courseReportUrl($course), ['reason' => 'duplicate', 'duplicate_of_id' => 999999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');

    $this->postJson(courseReportUrl($course), ['reason' => 'duplicate', 'duplicate_of_id' => $course->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');

    $this->postJson(courseReportUrl(Course::factory()->create()), [
        'reason' => 'other',
        'comment' => 'Something',
        'duplicate_of_id' => Course::factory()->create()->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');
});

it('requires a comment unless the report is a duplicate', function (string $reason) {
    $course = Course::factory()->create();

    $this->postJson(courseReportUrl($course), ['reason' => $reason])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('comment');
})->with(['false_data', 'other']);

it('does not accept the missing reason on an existing course', function () {
    $course = Course::factory()->create();

    $this->postJson(courseReportUrl($course), [
        'reason' => 'missing',
        'comment' => 'Suggestion',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');
});

it('suggests a new course for a department', function () {
    $department = Department::factory()->create();

    $this->postJson("/api/app/departments/{$department->id}/courses/reports", [
        'reason' => 'missing',
        'comment' => 'Linear Algebra III is missing',
    ])
        ->assertCreated()
        ->assertJsonPath('data.reason', 'missing')
        ->assertJsonPath('data.course', null)
        ->assertJsonPath('data.department.id', $department->id);

    $this->assertDatabaseHas('course_reports', [
        'user_id' => $this->user->id,
        'course_id' => null,
        'department_id' => $department->id,
        'reason' => 'missing',
    ]);
});

it('only accepts the missing reason when suggesting a course', function () {
    $department = Department::factory()->create();

    $url = "/api/app/departments/{$department->id}/courses/reports";

    $this->postJson($url, ['reason' => 'other', 'comment' => 'Something'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');

    $this->postJson($url, ['reason' => 'missing'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('comment');
});

it('throttles reports on the same course', function () {
    [$course, $other] = Course::factory()->count(2)->create();

    foreach (range(1, 3) as $ignored) {
        $this->postJson(courseReportUrl($course), [
            'reason' => 'other',
            'comment' => 'Again',
        ])->assertCreated();
    }

    $this->postJson(courseReportUrl($course), [
        'reason' => 'other',
        'comment' => 'Again',
    ])->assertTooManyRequests();

    $this->postJson(courseReportUrl($other), [
        'reason' => 'other',
        'comment' => 'Another course',
    ])->assertCreated();
});

it('throttles course suggestions per department', function () {
    [$department, $other] = Department::factory()->count(2)->create();

    foreach (range(1, 3) as $ignored) {
        $this->postJson("/api/app/departments/{$department->id}/courses/reports", [
            'reason' => 'missing',
            'comment' => 'Again',
        ])->assertCreated();
    }

    $this->postJson("/api/app/departments/{$department->id}/courses/reports", [
        'reason' => 'missing',
        'comment' => 'Again',
    ])->assertTooManyRequests();

    $this->postJson("/api/app/departments/{$other->id}/courses/reports", [
        'reason' => 'missing',
        'comment' => 'Another department',
    ])->assertCreated();
});

it('lists only the course reports of the authenticated user', function () {
    $mine = CourseReport::factory()->count(2)->for($this->user)->create();
    CourseReport::factory()->count(3)->create();

    $response = $this->getJson('/api/app/reports/courses')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect(collect($response->json('data'))->pluck('id')->all())
        ->toEqualCanonicalizing($mine->pluck('id')->all());
});

it('never exposes the reporter', function () {
    CourseReport::factory()->for($this->user)->resolved()->create();

    $response = $this->getJson('/api/app/reports/courses')->assertOk();

    expect($response->json('data.0'))->not->toHaveKeys(['user', 'user_id']);

    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/app/admin/reports/courses')->assertOk();

    expect($response->json('data.0'))->not->toHaveKeys(['user', 'user_id']);
    expect($response->getContent())->not->toContain($this->user->email);
});

it('only lets moderators and admins list every course report', function () {
    $this->getJson('/api/app/admin/reports/courses')->assertForbidden();

    Sanctum::actingAs(User::factory()->moderator()->create());

    $this->getJson('/api/app/admin/reports/courses')->assertOk();

    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson('/api/app/admin/reports/courses')->assertOk();
});

it('filters the course reports for the admin', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    CourseReport::factory()->count(2)->create();
    CourseReport::factory()->missing()->create();
    CourseReport::factory()->resolved()->create();

    $this->getJson('/api/app/admin/reports/courses')
        ->assertOk()
        ->assertJsonCount(4, 'data');

    $this->getJson('/api/app/admin/reports/courses?reason=missing')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/app/admin/reports/courses?resolved=1')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/app/admin/reports/courses?resolved=0')
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $this->getJson('/api/app/admin/reports/courses?reason=nope')
        ->assertUnprocessable();
});

it('lets a moderator or an admin resolve a course report once', function (string $role) {
    $resolver = User::factory()->$role()->create();
    Sanctum::actingAs($resolver);

    $report = CourseReport::factory()->create();

    $this->postJson("/api/app/admin/reports/courses/{$report->id}/resolve", [
        'comment' => 'Fixed the name',
    ])
        ->assertOk()
        ->assertJsonPath('data.resolved_by', $resolver->id)
        ->assertJsonPath('data.resolved_comment', 'Fixed the name');

    expect($report->fresh())
        ->resolved_at->not->toBeNull()
        ->resolved_by->toBe($resolver->id);

    $this->postJson("/api/app/admin/reports/courses/{$report->id}/resolve")
        ->assertConflict();
})->with(['moderator', 'admin']);

it('does not let regular users resolve a course report', function () {
    $report = CourseReport::factory()->for($this->user)->create();

    $this->postJson("/api/app/admin/reports/courses/{$report->id}/resolve")
        ->assertForbidden();

    expect($report->fresh()->resolved_at)->toBeNull();
});

it('requires authentication to report a course', function () {
    $course = Course::factory()->create();

    $this->app['auth']->forgetGuards();

    $this->postJson(courseReportUrl($course), ['reason' => 'other', 'comment' => 'x'])
        ->assertUnauthorized();
});

it('seeds a variety of course reports', function () {
    $this->seed(UserSeeder::class);
    $this->seed(DepartmentSeeder::class);
    $this->seed(CourseSeeder::class);
    $this->seed(CourseReportSeeder::class);

    expect(CourseReport::count())->toBeGreaterThan(0);

    foreach (DataReportReason::cases() as $reason) {
        expect(CourseReport::where('reason', $reason)->count())->toBeGreaterThan(0);
    }

    expect(CourseReport::resolved()->count())->toBeGreaterThan(0)
        ->and(CourseReport::unresolved()->count())->toBeGreaterThan(0)
        ->and(CourseReport::where('reason', DataReportReason::Missing)->whereNotNull('course_id')->count())->toBe(0)
        ->and(CourseReport::where('reason', DataReportReason::Missing)->whereNull('department_id')->count())->toBe(0)
        ->and(CourseReport::whereColumn('course_id', 'duplicate_of_id')->count())->toBe(0);
});

it('tells the department of the reported course', function () {
    $course = Course::factory()->create();

    $this->postJson(courseReportUrl($course), [
        'reason' => 'other',
        'comment' => 'Wrong',
    ])
        ->assertCreated()
        ->assertJsonPath('data.course.department.id', $course->department_id)
        ->assertJsonPath('data.course.department.name', $course->department->name);

    $this->getJson('/api/app/reports/courses')
        ->assertOk()
        ->assertJsonPath('data.0.course.department.id', $course->department_id);
});

it('tells the department of the duplicate course', function () {
    [$course, $original] = Course::factory()->count(2)->create();

    $this->postJson(courseReportUrl($course), [
        'reason' => 'duplicate',
        'duplicate_of_id' => $original->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.duplicate_of.department.id', $original->department_id);
});
