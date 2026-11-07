<?php

use App\Enums\DataReportReason;
use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\Department;
use App\Models\DepartmentReport;
use App\Models\User;
use Database\Seeders\DepartmentReportSeeder;
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

it('reports a department', function (string $reason) {
    $department = Department::factory()->create();

    $this->postJson("/api/app/departments/{$department->id}/reports", [
        'reason' => $reason,
        'comment' => 'The name is wrong',
    ])
        ->assertCreated()
        ->assertJsonPath('data.reason', $reason)
        ->assertJsonPath('data.department.id', $department->id)
        ->assertJsonPath('data.resolved_at', null);

    $this->assertDatabaseHas('department_reports', [
        'user_id' => $this->user->id,
        'department_id' => $department->id,
        'reason' => $reason,
    ]);
})->with(['false_data', 'other']);

it('reports a duplicate department', function () {
    [$department, $original] = Department::factory()->count(2)->create();

    $this->postJson("/api/app/departments/{$department->id}/reports", [
        'reason' => 'duplicate',
        'duplicate_of_id' => $original->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.duplicate_of.id', $original->id)
        ->assertJsonPath('data.comment', null);
});

it('validates the duplicate reference', function () {
    $department = Department::factory()->create();

    $url = "/api/app/departments/{$department->id}/reports";

    $this->postJson($url, ['reason' => 'duplicate'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');

    $this->postJson($url, ['reason' => 'duplicate', 'duplicate_of_id' => 999999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');

    $this->postJson($url, ['reason' => 'duplicate', 'duplicate_of_id' => $department->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');

    $this->postJson('/api/app/departments/'.Department::factory()->create()->id.'/reports', [
        'reason' => 'other',
        'comment' => 'Something',
        'duplicate_of_id' => Department::factory()->create()->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duplicate_of_id');
});

it('requires a comment unless the report is a duplicate', function (string $reason) {
    $department = Department::factory()->create();

    $this->postJson("/api/app/departments/{$department->id}/reports", ['reason' => $reason])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('comment');
})->with(['false_data', 'other']);

it('does not accept the missing reason on an existing department', function () {
    $department = Department::factory()->create();

    $this->postJson("/api/app/departments/{$department->id}/reports", [
        'reason' => 'missing',
        'comment' => 'Suggestion',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');
});

it('suggests a new department', function () {
    $this->postJson('/api/app/departments/reports', [
        'reason' => 'missing',
        'comment' => 'Department of Geology is missing',
    ])
        ->assertCreated()
        ->assertJsonPath('data.reason', 'missing')
        ->assertJsonPath('data.department', null);

    $this->assertDatabaseHas('department_reports', [
        'user_id' => $this->user->id,
        'department_id' => null,
        'reason' => 'missing',
    ]);
});

it('only accepts the missing reason without a department', function () {
    $this->postJson('/api/app/departments/reports', [
        'reason' => 'other',
        'comment' => 'Something',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');

    $this->postJson('/api/app/departments/reports', ['reason' => 'missing'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('comment');
});

it('throttles reports on the same department', function () {
    [$department, $other] = Department::factory()->count(2)->create();

    foreach (range(1, 3) as $ignored) {
        $this->postJson("/api/app/departments/{$department->id}/reports", [
            'reason' => 'other',
            'comment' => 'Again',
        ])->assertCreated();
    }

    $this->postJson("/api/app/departments/{$department->id}/reports", [
        'reason' => 'other',
        'comment' => 'Again',
    ])->assertTooManyRequests();

    $this->postJson("/api/app/departments/{$other->id}/reports", [
        'reason' => 'other',
        'comment' => 'Another department',
    ])->assertCreated();
});

it('lists only the reports of the authenticated user', function () {
    $mine = DepartmentReport::factory()->count(2)->for($this->user)->create();
    DepartmentReport::factory()->count(3)->create();

    $response = $this->getJson('/api/app/reports/departments')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect(collect($response->json('data'))->pluck('id')->all())
        ->toEqualCanonicalizing($mine->pluck('id')->all());
});

it('never exposes the reporter', function () {
    $report = DepartmentReport::factory()->for($this->user)->resolved()->create();

    $response = $this->getJson('/api/app/reports/departments')->assertOk();

    expect($response->json('data.0'))->not->toHaveKeys(['user', 'user_id']);

    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/app/admin/reports/departments')->assertOk();

    expect($response->json('data.0'))->not->toHaveKeys(['user', 'user_id']);
    expect($response->getContent())->not->toContain($this->user->email);
});

it('only lets moderators and admins list every report', function () {
    $this->getJson('/api/app/admin/reports/departments')->assertForbidden();

    Sanctum::actingAs(User::factory()->moderator()->create());

    $this->getJson('/api/app/admin/reports/departments')->assertOk();

    Sanctum::actingAs(User::factory()->admin()->create());

    $this->getJson('/api/app/admin/reports/departments')->assertOk();
});

it('filters the reports for the admin', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    DepartmentReport::factory()->count(2)->create();
    DepartmentReport::factory()->missing()->create();
    DepartmentReport::factory()->resolved()->create();

    $this->getJson('/api/app/admin/reports/departments')
        ->assertOk()
        ->assertJsonCount(4, 'data');

    $this->getJson('/api/app/admin/reports/departments?reason=missing')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/app/admin/reports/departments?resolved=1')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/app/admin/reports/departments?resolved=0')
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $this->getJson('/api/app/admin/reports/departments?reason=nope')
        ->assertUnprocessable();
});

it('lets a moderator or an admin resolve a report once', function (string $role) {
    $resolver = User::factory()->$role()->create();
    Sanctum::actingAs($resolver);

    $report = DepartmentReport::factory()->create();

    $this->postJson("/api/app/admin/reports/departments/{$report->id}/resolve", [
        'comment' => 'Fixed the name',
    ])
        ->assertOk()
        ->assertJsonPath('data.resolved_by', $resolver->id)
        ->assertJsonPath('data.resolved_comment', 'Fixed the name');

    expect($report->fresh())
        ->resolved_at->not->toBeNull()
        ->resolved_by->toBe($resolver->id);

    $this->postJson("/api/app/admin/reports/departments/{$report->id}/resolve")
        ->assertConflict();
})->with(['moderator', 'admin']);

it('does not let regular users resolve a report', function () {
    $report = DepartmentReport::factory()->for($this->user)->create();

    $this->postJson("/api/app/admin/reports/departments/{$report->id}/resolve")
        ->assertForbidden();

    expect($report->fresh()->resolved_at)->toBeNull();
});

it('requires authentication', function () {
    $this->app['auth']->forgetGuards();

    $this->postJson('/api/app/departments/reports', ['reason' => 'missing', 'comment' => 'x'])
        ->assertUnauthorized();
});

it('seeds a variety of department reports', function () {
    $this->seed(UserSeeder::class);
    $this->seed(DepartmentSeeder::class);
    $this->seed(DepartmentReportSeeder::class);

    expect(DepartmentReport::count())->toBeGreaterThan(0);

    foreach (DataReportReason::cases() as $reason) {
        expect(DepartmentReport::where('reason', $reason)->count())->toBeGreaterThan(0);
    }

    expect(DepartmentReport::resolved()->count())->toBeGreaterThan(0)
        ->and(DepartmentReport::unresolved()->count())->toBeGreaterThan(0)
        ->and(DepartmentReport::where('reason', DataReportReason::Missing)->whereNotNull('department_id')->count())->toBe(0)
        ->and(DepartmentReport::whereColumn('department_id', 'duplicate_of_id')->count())->toBe(0);
});
