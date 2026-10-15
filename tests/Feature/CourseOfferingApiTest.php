<?php

use App\Enums\Semester;
use App\Http\Middleware\UserHasAcceptedLegalDocuments;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(UserHasAcceptedLegalDocuments::class);
    Sanctum::actingAs(User::factory()->create());
});

function offeringPayload(Course $course, array $overrides = []): array
{
    return [
        'course_id' => $course->id,
        'semester' => 'Fall',
        'year' => 2025,
        ...$overrides,
    ];
}

it('lists offerings with their course and filters them', function () {
    $course = Course::factory()->create();
    $match = CourseOffering::factory()->for($course)->create(['semester' => Semester::Fall, 'year' => 2025]);
    CourseOffering::factory()->for($course)->create(['semester' => Semester::Spring, 'year' => 2025]);
    CourseOffering::factory()->for($course)->create(['semester' => Semester::Fall, 'year' => 2024]);
    CourseOffering::factory()->create(['semester' => Semester::Fall, 'year' => 2025]);

    $this->getJson('/api/app/course-offerings')
        ->assertOk()
        ->assertJsonCount(4, 'data');

    $this->getJson("/api/app/course-offerings?course_id={$course->id}&semester=Fall&year=2025")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('data.0.semester', 'Fall')
        ->assertJsonPath('data.0.course.code', $course->code);

    $this->getJson('/api/app/course-offerings?semester=Winter')->assertUnprocessable();
});

it('shows an offering with its professors', function () {
    $offering = CourseOffering::factory()->create();
    $professor = Professor::factory()->create();
    $offering->professors()->attach($professor);

    $this->getJson("/api/app/course-offerings/{$offering->id}")
        ->assertOk()
        ->assertJsonPath('data.professors.0.id', $professor->id)
        ->assertJsonPath('data.course.id', $offering->course_id);
});

it('creates an offering with professors and logs every change', function () {
    $course = Course::factory()->create();
    $professors = Professor::factory()->count(2)->create();

    $response = $this->postJson('/api/app/course-offerings', offeringPayload($course, [
        'professor_ids' => $professors->pluck('id')->all(),
    ]))->assertCreated()
        ->assertJsonPath('data.semester', 'Fall')
        ->assertJsonCount(2, 'data.professors');

    expect(Activity::where('subject_type', CourseOffering::class)->pluck('event')->all())
        ->toBe(['created', 'attached'])
        ->and(Activity::where('event', 'attached')->first()->properties['ids'])
        ->toBe($professors->pluck('id')->all())
        ->and($response->json('data.id'))->toBe(CourseOffering::first()->id);
});

it('validates the fields and the references', function () {
    $this->postJson('/api/app/course-offerings', [])->assertUnprocessable()
        ->assertJsonValidationErrors(['course_id', 'semester', 'year']);

    $deleted = Course::factory()->create();
    $deleted->delete();
    $course = Course::factory()->create();
    $deletedProfessor = Professor::factory()->create();
    $deletedProfessor->delete();

    $this->postJson('/api/app/course-offerings', offeringPayload($course, ['semester' => 'Winter', 'year' => 1500]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['semester', 'year']);

    foreach ([999, $deleted->id] as $courseId) {
        $this->postJson('/api/app/course-offerings', offeringPayload($course, ['course_id' => $courseId]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('course_id');
    }

    $this->postJson('/api/app/course-offerings', offeringPayload($course, [
        'professor_ids' => [999, $deletedProfessor->id],
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors(['professor_ids.0', 'professor_ids.1']);

    expect(CourseOffering::count())->toBe(0);
});

it('allows one offering per course, semester and year', function () {
    $course = Course::factory()->create();
    $offering = CourseOffering::factory()->for($course)->create(['semester' => Semester::Fall, 'year' => 2025]);

    $this->postJson('/api/app/course-offerings', offeringPayload($course))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('year');

    $this->postJson('/api/app/course-offerings', offeringPayload($course, ['semester' => 'Spring']))->assertCreated();
    $this->postJson('/api/app/course-offerings', offeringPayload(Course::factory()->create()))->assertCreated();

    // Keeping its own key is fine.
    $this->patchJson("/api/app/course-offerings/{$offering->id}", ['year' => 2025])->assertOk();
});

it('brings back a deleted offering when it is created again', function () {
    $course = Course::factory()->create();
    $offering = CourseOffering::factory()->for($course)->create(['semester' => Semester::Fall, 'year' => 2025]);
    $professor = Professor::factory()->create();
    $offering->professors()->attach($professor);
    $offering->delete();

    $this->postJson('/api/app/course-offerings', offeringPayload($course))
        ->assertCreated()
        ->assertJsonPath('data.id', $offering->id)
        ->assertJsonPath('data.professors.0.id', $professor->id);

    expect(CourseOffering::count())->toBe(1)
        ->and(Activity::latest('id')->first()->event)->toBe('restored');
});

it('checks uniqueness against the resulting key when updating', function () {
    $course = Course::factory()->create();
    CourseOffering::factory()->for($course)->create(['semester' => Semester::Fall, 'year' => 2025]);
    $offering = CourseOffering::factory()->for($course)->create(['semester' => Semester::Spring, 'year' => 2025]);

    $this->patchJson("/api/app/course-offerings/{$offering->id}", ['semester' => 'Fall'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('year');
});

it('does not let an update reuse the key of a deleted offering', function () {
    $course = Course::factory()->create();
    $deleted = CourseOffering::factory()->for($course)->create(['semester' => Semester::Fall, 'year' => 2025]);
    $deleted->delete();
    $offering = CourseOffering::factory()->for($course)->create(['semester' => Semester::Spring, 'year' => 2025]);

    $this->patchJson("/api/app/course-offerings/{$offering->id}", ['semester' => 'Fall'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('year');
});

it('replaces professors, logging what was attached and detached', function () {
    [$a, $b, $c] = Professor::factory()->count(3)->create()->all();
    $offering = CourseOffering::factory()->create();
    $offering->professors()->attach([$a->id, $b->id]);

    $this->patchJson("/api/app/course-offerings/{$offering->id}", ['professor_ids' => [$b->id, $c->id]])
        ->assertOk();

    expect($offering->professors()->pluck('professors.id')->all())->toEqualCanonicalizing([$b->id, $c->id])
        ->and(Activity::where('event', 'detached')->latest('id')->first()->properties['ids'])->toBe([$a->id])
        ->and(Activity::where('event', 'attached')->latest('id')->first()->properties['ids'])->toBe([$c->id]);
});

it('updates the key only and leaves professors alone', function () {
    $offering = CourseOffering::factory()->create(['year' => 2020]);
    $offering->professors()->attach(Professor::factory()->create());

    $this->patchJson("/api/app/course-offerings/{$offering->id}", ['year' => 2021])
        ->assertOk()
        ->assertJsonPath('data.year', 2021)
        ->assertJsonCount(1, 'data.professors');

    $activity = Activity::latest('id')->first();

    expect($activity->event)->toBe('updated')
        ->and($activity->attribute_changes['attributes'])->toBe(['year' => 2021]);
});

it('soft deletes an offering and hides it afterwards', function () {
    $offering = CourseOffering::factory()->create();

    $this->deleteJson("/api/app/course-offerings/{$offering->id}")->assertNoContent();

    $this->getJson("/api/app/course-offerings/{$offering->id}")->assertNotFound();
    expect(CourseOffering::withTrashed()->count())->toBe(1)
        ->and(Activity::latest('id')->first()->event)->toBe('deleted');
});

it('shows the history of an offering including links made from the professor side', function () {
    $offering = CourseOffering::factory()->create(['year' => 2020]);
    $other = CourseOffering::factory()->create();
    $professor = Professor::factory()->create();

    $offering->update(['year' => 2021]);
    $professor->courseOfferings()->attach($offering);
    $professor->courseOfferings()->attach($other);
    $other->update(['year' => 2022]);

    $response = $this->getJson("/api/app/course-offerings/{$offering->id}/activity")->assertOk();

    expect(collect($response->json('data'))->pluck('event')->all())
        ->toBe(['attached', 'updated', 'created']);
});

it('shows the history of a deleted offering', function () {
    $offering = CourseOffering::factory()->create();
    $offering->delete();

    $this->getJson("/api/app/course-offerings/{$offering->id}/activity")
        ->assertOk()
        ->assertJsonPath('data.0.event', 'deleted');
});

it('requires authentication', function () {
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/app/course-offerings')->assertUnauthorized();
});
