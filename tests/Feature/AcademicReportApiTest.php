<?php

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseOfferingReport;
use App\Models\CourseReport;
use App\Models\Department;
use App\Models\DepartmentReport;
use App\Models\PrivacyPolicy;
use App\Models\Professor;
use App\Models\ProfessorReport;
use App\Models\TermsOfUse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function signInForReports(User $user): void
{
    $privacyPolicy = PrivacyPolicy::latest('id')->first() ?? PrivacyPolicy::factory()->create();
    $termsOfUse = TermsOfUse::latest('id')->first() ?? TermsOfUse::factory()->create();

    $user->acceptedPrivacyPolicies()->attach($privacyPolicy->id);
    $user->acceptedTermsOfUses()->attach($termsOfUse->id);

    Sanctum::actingAs($user);
}

/**
 * @return array<string, array{0: string, 1: string}>
 */
function reportTargets(): array
{
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();
    $offering = CourseOffering::factory()->for($course)->create();
    $professor = Professor::factory()->create();
    $professor->departments()->attach($department);

    return [
        'department_reports' => ["departments/{$department->id}/reports", 'department_id'],
        'course_reports' => ["departments/{$department->id}/courses/{$course->id}/reports", 'course_id'],
        'professor_reports' => ["departments/{$department->id}/professors/{$professor->id}/reports", 'professor_id'],
        'course_offering_reports' => ["departments/{$department->id}/courses/{$course->id}/offerings/{$offering->id}/reports", 'course_offering_id'],
    ];
}

it('lets a user report each catalog entity without affecting it', function () {
    signInForReports(User::factory()->create());

    foreach (reportTargets() as $table => [$uri]) {
        $this->postJson("/api/app/{$uri}", ['comment' => 'Wrong info'])
            ->assertCreated()
            ->assertJsonPath('data.comment', 'Wrong info')
            ->assertJsonPath('data.resolved_at', null);

        $this->assertDatabaseCount($table, 1);
    }

    expect(Department::count())->toBe(1)
        ->and(Course::count())->toBe(1)
        ->and(Professor::count())->toBe(1)
        ->and(CourseOffering::count())->toBe(1);
});

it('lets a user report the same entity multiple times but throttles them', function () {
    signInForReports(User::factory()->create());

    [$uri] = reportTargets()['department_reports'];

    foreach (range(1, 3) as $_) {
        $this->postJson("/api/app/{$uri}", ['comment' => 'Again'])->assertCreated();
    }

    $this->postJson("/api/app/{$uri}", ['comment' => 'Again'])->assertTooManyRequests();

    $this->assertDatabaseCount('department_reports', 3);
});

it('requires a comment and authentication', function () {
    [$uri] = reportTargets()['department_reports'];

    $this->postJson("/api/app/{$uri}", ['comment' => 'x'])->assertUnauthorized();

    signInForReports(User::factory()->create());

    $this->postJson("/api/app/{$uri}")->assertUnprocessable();
});

it('lets only moderators list reports filtered by resolved', function () {
    $targets = reportTargets();

    signInForReports(User::factory()->create());

    foreach ($targets as [$uri]) {
        $this->getJson("/api/app/{$uri}")->assertForbidden();
    }

    $moderator = User::factory()->moderator()->create();
    signInForReports($moderator);

    $department = Department::first();
    DepartmentReport::factory()->department($department)->create();
    DepartmentReport::factory()->department($department)->resolved($moderator)->create();

    $uri = $targets['department_reports'][0];

    $this->getJson("/api/app/{$uri}")->assertOk()->assertJsonCount(2, 'data');
    $this->getJson("/api/app/{$uri}?resolved=1")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/app/{$uri}?resolved=0")->assertOk()->assertJsonCount(1, 'data');

    foreach ($targets as [$uri]) {
        $this->getJson("/api/app/{$uri}")->assertOk();
    }
});

it('hides who resolved a report and lets only that moderator remove the resolution', function () {
    $targets = reportTargets();
    $department = Department::first();

    $resolver = User::factory()->moderator()->create();
    $other = User::factory()->moderator()->create();

    $report = DepartmentReport::factory()->department($department)->resolved($resolver)->create();
    $uri = $targets['department_reports'][0];

    signInForReports($other);

    $this->getJson("/api/app/{$uri}")
        ->assertOk()
        ->assertJsonPath('data.0.resolved_by_me', false)
        ->assertJsonMissingPath('data.0.resolved_by_user_id')
        ->assertJsonMissingPath('data.0.user_id');

    $this->deleteJson("/api/app/{$uri}/{$report->id}/resolution")->assertForbidden();

    signInForReports($resolver);

    $this->getJson("/api/app/{$uri}")->assertJsonPath('data.0.resolved_by_me', true);

    $this->deleteJson("/api/app/{$uri}/{$report->id}/resolution")->assertNoContent();

    expect($report->fresh())
        ->resolved_at->toBeNull()
        ->resolved_by_user_id->toBeNull()
        ->resolve_comment->toBeNull();
});

it('forbids regular users from removing a resolution', function () {
    $targets = reportTargets();
    $moderator = User::factory()->moderator()->create();
    $report = DepartmentReport::factory()->department(Department::first())->resolved($moderator)->create();

    signInForReports(User::factory()->create());

    $this->deleteJson('/api/app/'.$targets['department_reports'][0]."/{$report->id}/resolution")->assertForbidden();
});

it('lets a moderator resolve each kind of report once', function () {
    $targets = reportTargets();
    $moderator = User::factory()->moderator()->create();

    $factories = [
        'department_reports' => fn () => DepartmentReport::factory()->department(Department::first()),
        'course_reports' => fn () => CourseReport::factory()->course(Course::first()),
        'professor_reports' => fn () => ProfessorReport::factory()->professor(Professor::first()),
        'course_offering_reports' => fn () => CourseOfferingReport::factory()->courseOffering(CourseOffering::first()),
    ];

    signInForReports(User::factory()->create());
    $report = $factories['department_reports']()->create();
    $this->postJson('/api/app/'.$targets['department_reports'][0]."/{$report->id}/resolution", ['resolve_comment' => 'Done'])
        ->assertForbidden();

    signInForReports($moderator);

    foreach ($factories as $table => $factory) {
        $uri = '/api/app/'.$targets[$table][0];
        $report = $factory()->create();

        $this->postJson("{$uri}/{$report->id}/resolution")->assertUnprocessable();

        $this->postJson("{$uri}/{$report->id}/resolution", ['resolve_comment' => 'Fixed'])
            ->assertOk()
            ->assertJsonPath('data.resolve_comment', 'Fixed')
            ->assertJsonPath('data.resolved_by_me', true);

        $this->postJson("{$uri}/{$report->id}/resolution", ['resolve_comment' => 'Again'])
            ->assertConflict();

        expect($report->fresh())
            ->resolved_by_user_id->toBe($moderator->id)
            ->resolved_at->not->toBeNull();
    }
});
