<?php

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseOfferingReview;
use App\Models\Department;
use App\Models\PrivacyPolicy;
use App\Models\TermsOfUse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function signInForReviews(User $user): void
{
    $privacyPolicy = PrivacyPolicy::latest('id')->first() ?? PrivacyPolicy::factory()->create();
    $termsOfUse = TermsOfUse::latest('id')->first() ?? TermsOfUse::factory()->create();

    $user->acceptedPrivacyPolicies()->attach($privacyPolicy->id);
    $user->acceptedTermsOfUses()->attach($termsOfUse->id);

    Sanctum::actingAs($user);
}

function reviewBase(CourseOffering $offering): string
{
    return "/api/app/departments/{$offering->course->department_id}/courses/{$offering->course_id}/offerings/{$offering->id}/reviews";
}

beforeEach(function () {
    $department = Department::factory()->create();
    $course = Course::factory()->for($department)->create();
    $this->offering = CourseOffering::factory()->for($course)->create();
    $this->base = reviewBase($this->offering);
});

it('lists non deleted reviews of everyone without revealing authors', function () {
    $me = User::factory()->create();
    CourseOfferingReview::factory()->courseOffering($this->offering)->user($me)->create();
    CourseOfferingReview::factory()->courseOffering($this->offering)->create();
    CourseOfferingReview::factory()->courseOffering($this->offering)->create()->delete();

    signInForReviews($me);

    $response = $this->getJson($this->base)->assertOk()->assertJsonCount(2, 'data');

    expect(collect($response->json('data'))->pluck('is_own')->sort()->values()->all())->toBe([false, true]);
    $response->assertJsonMissingPath('data.0.user_id');
});

it('does not show a deleted review', function () {
    $review = CourseOfferingReview::factory()->courseOffering($this->offering)->create();
    $review->delete();

    signInForReviews(User::factory()->create());

    $this->getJson("{$this->base}/{$review->id}")->assertNotFound();
});

it('lets a user create one review per offering', function () {
    signInForReviews($user = User::factory()->create());

    $this->postJson($this->base)->assertUnprocessable();
    $this->postJson($this->base, ['rating' => 6])->assertUnprocessable();

    $this->postJson($this->base, ['rating' => 4, 'comment' => 'Nice'])
        ->assertCreated()
        ->assertJsonPath('data.is_own', true);

    $this->postJson($this->base, ['rating' => 5])->assertConflict();

    expect($user->courseOfferingReviews()->count())->toBe(1);
});

it('lets a user update and delete only their own review', function () {
    $mine = CourseOfferingReview::factory()->courseOffering($this->offering)->user($me = User::factory()->create())->create(['rating' => 2]);
    $theirs = CourseOfferingReview::factory()->courseOffering($this->offering)->create();

    signInForReviews($me);

    $this->patchJson("{$this->base}/{$mine->id}", ['rating' => 5])->assertOk()->assertJsonPath('data.rating', 5);
    $this->patchJson("{$this->base}/{$theirs->id}", ['rating' => 5])->assertForbidden();
    $this->deleteJson("{$this->base}/{$theirs->id}")->assertForbidden();

    $this->deleteJson("{$this->base}/{$mine->id}")->assertNoContent();
    $this->assertSoftDeleted($mine);
});

it('restores a deleted review when the user reviews again', function () {
    $review = CourseOfferingReview::factory()->courseOffering($this->offering)->user($me = User::factory()->create())->create(['comment' => 'old']);
    $review->delete();

    signInForReviews($me);

    $this->postJson($this->base, ['rating' => 3])->assertCreated()->assertJsonPath('data.comment', null);

    expect(CourseOfferingReview::withTrashed()->count())->toBe(1);
});

it('requires authentication', function () {
    $this->getJson($this->base)->assertUnauthorized();
});
