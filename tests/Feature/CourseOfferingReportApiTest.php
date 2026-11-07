<?php

use App\Enums\DataReportReason;
use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseOfferingReport;
use App\Models\User;
use Database\Seeders\CourseOfferingReportSeeder;
use Database\Seeders\CourseOfferingSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\InstructorSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(UserHasAcceptedLegalDocuments::class);

    $this->user = User::factory()->create();

    Sanctum::actingAs($this->user);
});

function offeringReportUrl(CourseOffering $offering): string
{
    $course = $offering->course;

    return "/api/app/departments/{$course->department_id}/courses/{$course->id}/offerings/{$offering->id}/reports";
}

function offeringSuggestUrl(Course $course): string
{
    return "/api/app/departments/{$course->department_id}/courses/{$course->id}/offerings/reports";
}

it('reports a course offering', function (string $reason) {
    $offering = CourseOffering::factory()->create();

    $this->postJson(offeringReportUrl($offering), [
        'reason' => $reason,
        'comment' => 'The semester is wrong',
    ])
        ->assertCreated()
        ->assertJsonPath('data.reason', $reason)
        ->assertJsonPath('data.offering.id', $offering->id)
        ->assertJsonPath('data.resolved_at', null);

    $this->assertDatabaseHas('course_offering_reports', [
        'user_id' => $this->user->id,
        'course_offering_id' => $offering->id,
        'course_id' => null,
        'reason' => $reason,
    ]);
})->with(['false_data', 'other']);

it('does not report an offering through another course', function () {
    $offering = CourseOffering::factory()->create();
    $course = Course::factory()->create();

    $this->postJson("/api/app/departments/{$course->department_id}/courses/{$course->id}/offerings/{$offering->id}/reports", [
        'reason' => 'other',
        'comment' => 'Wrong course',
    ])->assertNotFound();
});

it('reports a duplicate offering', function () {
    [$offering, $original] = CourseOffering::factory()->count(2)->create();

    $this->postJson(offeringReportUrl($offering), [
        'reason' => 'duplicate',
        'duplicate_of_id' => $original->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.duplicate_of.id', $original->id)
        ->assertJsonPath('data.comment', null);
});

it('validates the duplicate reference', function () {
    $offering = CourseOffering::factory()->create();

    $this->postJson(offeringReportUrl($offering), ['reason' => 'duplicate'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');

    $this->postJson(offeringReportUrl($offering), ['reason' => 'duplicate', 'duplicate_of_id' => 999999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');

    $this->postJson(offeringReportUrl($offering), ['reason' => 'duplicate', 'duplicate_of_id' => $offering->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');

    $this->postJson(offeringReportUrl(CourseOffering::factory()->create()), [
        'reason' => 'other',
        'comment' => 'Something',
        'duplicate_of_id' => CourseOffering::factory()->create()->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');
});

it('requires a comment unless the report is a duplicate', function (string $reason) {
    $offering = CourseOffering::factory()->create();

    $this->postJson(offeringReportUrl($offering), ['reason' => $reason])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('comment');
})->with(['false_data', 'other']);

it('does not accept the missing reason on an existing offering', function () {
    $offering = CourseOffering::factory()->create();

    $this->postJson(offeringReportUrl($offering), [
        'reason' => 'missing',
        'comment' => 'Suggestion',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');
});

it('suggests a new offering for a course', function () {
    $course = Course::factory()->create();

    $this->postJson(offeringSuggestUrl($course), [
        'reason' => 'missing',
        'comment' => 'Spring 2025 is missing',
    ])
        ->assertCreated()
        ->assertJsonPath('data.reason', 'missing')
        ->assertJsonPath('data.offering', null)
        ->assertJsonPath('data.course.id', $course->id);

    $this->assertDatabaseHas('course_offering_reports', [
        'user_id' => $this->user->id,
        'course_offering_id' => null,
        'course_id' => $course->id,
        'reason' => 'missing',
    ]);
});

it('only accepts the missing reason when suggesting an offering', function () {
    $course = Course::factory()->create();

    $this->postJson(offeringSuggestUrl($course), ['reason' => 'other', 'comment' => 'Something'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');

    $this->postJson(offeringSuggestUrl($course), ['reason' => 'missing'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('comment');
});

it('throttles reports on the same offering', function () {
    [$offering, $other] = CourseOffering::factory()->count(2)->create();

    foreach (range(1, 3) as $ignored) {
        $this->postJson(offeringReportUrl($offering), [
            'reason' => 'other',
            'comment' => 'Again',
        ])->assertCreated();
    }

    $this->postJson(offeringReportUrl($offering), [
        'reason' => 'other',
        'comment' => 'Again',
    ])->assertTooManyRequests();

    $this->postJson(offeringReportUrl($other), [
        'reason' => 'other',
        'comment' => 'Another offering',
    ])->assertCreated();
});

it('throttles offering suggestions per course', function () {
    [$course, $other] = Course::factory()->count(2)->create();

    foreach (range(1, 3) as $ignored) {
        $this->postJson(offeringSuggestUrl($course), [
            'reason' => 'missing',
            'comment' => 'Again',
        ])->assertCreated();
    }

    $this->postJson(offeringSuggestUrl($course), [
        'reason' => 'missing',
        'comment' => 'Again',
    ])->assertTooManyRequests();

    $this->postJson(offeringSuggestUrl($other), [
        'reason' => 'missing',
        'comment' => 'Another course',
    ])->assertCreated();
});

it('lists only the offering reports of the authenticated user', function () {
    $mine = CourseOfferingReport::factory()->count(2)->for($this->user)->create();
    CourseOfferingReport::factory()->count(3)->create();

    $response = $this->getJson('/api/app/reports/offerings')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect(collect($response->json('data'))->pluck('id')->all())
        ->toEqualCanonicalizing($mine->pluck('id')->all());
});

it('never exposes the reporter', function () {
    CourseOfferingReport::factory()->for($this->user)->resolved()->create();

    $response = $this->getJson('/api/app/reports/offerings')->assertOk();

    expect($response->json('data.0'))->not->toHaveKeys(['user', 'user_id']);

    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/app/admin/reports/offerings')->assertOk();

    expect($response->json('data.0'))->not->toHaveKeys(['user', 'user_id']);
    expect($response->getContent())->not->toContain($this->user->email);
});

it('only lets moderators and admins list every offering report', function () {
    $this->getJson('/api/app/admin/reports/offerings')->assertForbidden();

    Sanctum::actingAs(User::factory()->moderator()->create());

    $this->getJson('/api/app/admin/reports/offerings')->assertOk();

    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson('/api/app/admin/reports/offerings')->assertOk();
});

it('filters the offering reports for the admin', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    CourseOfferingReport::factory()->count(2)->create();
    CourseOfferingReport::factory()->missing()->create();
    CourseOfferingReport::factory()->resolved()->create();

    $this->getJson('/api/app/admin/reports/offerings')
        ->assertOk()
        ->assertJsonCount(4, 'data');

    $this->getJson('/api/app/admin/reports/offerings?reason=missing')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/app/admin/reports/offerings?resolved=1')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/app/admin/reports/offerings?resolved=0')
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $this->getJson('/api/app/admin/reports/offerings?reason=nope')
        ->assertUnprocessable();
});

it('lets a moderator or an admin resolve an offering report once', function (string $role) {
    $resolver = User::factory()->$role()->create();
    Sanctum::actingAs($resolver);

    $report = CourseOfferingReport::factory()->create();

    $this->postJson("/api/app/admin/reports/offerings/{$report->id}/resolve", [
        'comment' => 'Fixed the semester',
    ])
        ->assertOk()
        ->assertJsonPath('data.resolved_by', $resolver->id)
        ->assertJsonPath('data.resolved_comment', 'Fixed the semester');

    expect($report->fresh())
        ->resolved_at->not->toBeNull()
        ->resolved_by->toBe($resolver->id);

    $this->postJson("/api/app/admin/reports/offerings/{$report->id}/resolve")
        ->assertConflict();
})->with(['moderator', 'admin']);

it('does not let regular users resolve an offering report', function () {
    $report = CourseOfferingReport::factory()->for($this->user)->create();

    $this->postJson("/api/app/admin/reports/offerings/{$report->id}/resolve")
        ->assertForbidden();

    expect($report->fresh()->resolved_at)->toBeNull();
});

it('requires authentication to report an offering', function () {
    $offering = CourseOffering::factory()->create();

    $this->app['auth']->forgetGuards();

    $this->postJson(offeringReportUrl($offering), ['reason' => 'other', 'comment' => 'x'])
        ->assertUnauthorized();
});

it('seeds a variety of offering reports', function () {
    $this->seed(UserSeeder::class);
    $this->seed(DepartmentSeeder::class);
    $this->seed(CourseSeeder::class);
    $this->seed(InstructorSeeder::class);
    $this->seed(CourseOfferingSeeder::class);
    $this->seed(CourseOfferingReportSeeder::class);

    expect(CourseOfferingReport::count())->toBeGreaterThan(0);

    foreach (DataReportReason::cases() as $reason) {
        expect(CourseOfferingReport::where('reason', $reason)->count())->toBeGreaterThan(0);
    }

    expect(CourseOfferingReport::resolved()->count())->toBeGreaterThan(0)
        ->and(CourseOfferingReport::unresolved()->count())->toBeGreaterThan(0)
        ->and(CourseOfferingReport::where('reason', DataReportReason::Missing)->whereNotNull('course_offering_id')->count())->toBe(0)
        ->and(CourseOfferingReport::where('reason', DataReportReason::Missing)->whereNull('course_id')->count())->toBe(0)
        ->and(CourseOfferingReport::whereColumn('course_offering_id', 'duplicate_of_id')->count())->toBe(0);
});
