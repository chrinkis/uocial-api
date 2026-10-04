<?php

use App\Models\PrivacyPolicy;
use App\Models\TermsOfUse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function acceptLatestLegalDocuments(User $user): void
{
    $privacyPolicy = PrivacyPolicy::latest('id')->first() ?? PrivacyPolicy::factory()->create();
    $termsOfUse = TermsOfUse::latest('id')->first() ?? TermsOfUse::factory()->create();

    $user->acceptedPrivacyPolicies()->attach($privacyPolicy->id);
    $user->acceptedTermsOfUses()->attach($termsOfUse->id);
}

function actingAsAdmin(): User
{
    $admin = User::factory()->admin()->create();
    acceptLatestLegalDocuments($admin);
    Sanctum::actingAs($admin);

    return $admin;
}

it('forbids moderators and regular users from ban routes', function (User $actor) {
    $target = User::factory()->create();
    acceptLatestLegalDocuments($actor);
    Sanctum::actingAs($actor);

    $this->getJson("/api/app/users/{$target->id}/bans")->assertForbidden();
    $this->postJson("/api/app/users/{$target->id}/bans", ['reason' => 'Spam'])->assertForbidden();
    $this->postJson("/api/app/users/{$target->id}/unban")->assertForbidden();

    expect($target->isBanned())->toBeFalse();
})->with([
    'regular' => fn () => User::factory()->create(),
    'moderator' => fn () => User::factory()->moderator()->create(),
]);

it('bans a user as an admin', function () {
    $admin = actingAsAdmin();
    $target = User::factory()->create();

    $this->postJson("/api/app/users/{$target->id}/bans", [
        'reason' => 'Spam',
        'expires_at' => now()->addDays(7)->toIso8601String(),
        'notes' => 'First offence',
    ])
        ->assertCreated()
        ->assertJsonPath('data.reason', 'Spam')
        ->assertJsonPath('data.notes', 'First offence')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.banned_by', $admin->id);

    expect($target->isBanned())->toBeTrue();
});

it('bans a user permanently when no expiry is given', function () {
    actingAsAdmin();
    $target = User::factory()->create();

    $this->postJson("/api/app/users/{$target->id}/bans", ['reason' => 'Abuse'])
        ->assertCreated()
        ->assertJsonPath('data.expires_at', null);

    expect($target->bans()->first()->expires_at)->toBeNull();
});

it('requires a reason to ban', function () {
    actingAsAdmin();
    $target = User::factory()->create();

    $this->postJson("/api/app/users/{$target->id}/bans", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');
});

it('rejects a ban in the past', function () {
    actingAsAdmin();
    $target = User::factory()->create();

    $this->postJson("/api/app/users/{$target->id}/bans", [
        'reason' => 'Spam',
        'expires_at' => now()->subDay()->toIso8601String(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('expires_at');
});

it('prevents an admin from banning themselves', function () {
    $admin = actingAsAdmin();

    $this->postJson("/api/app/users/{$admin->id}/bans", ['reason' => 'Oops'])
        ->assertUnprocessable();

    expect($admin->isBanned())->toBeFalse();
});

it('prevents banning a user who is already banned', function () {
    $admin = actingAsAdmin();
    $target = User::factory()->create();
    $target->ban('Spam', $admin);

    $this->postJson("/api/app/users/{$target->id}/bans", ['reason' => 'Again'])
        ->assertConflict();
});

it('returns the full ban history including lifted bans', function () {
    $admin = actingAsAdmin();
    $target = User::factory()->create();

    $target->ban('First', $admin);
    $target->unban($admin);
    $target->ban('Second', $admin, null, 'Repeat offence');

    $this->getJson("/api/app/users/{$target->id}/bans")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.reason', 'Second')
        ->assertJsonPath('data.0.is_active', true)
        ->assertJsonPath('data.1.reason', 'First')
        ->assertJsonPath('data.1.is_active', false)
        ->assertJsonPath('data.1.lifted_by', $admin->id)
        ->assertJsonPath('data.1.lifted_at', fn ($value) => $value !== null);
});

it('unbans a user as an admin and records who lifted it', function () {
    $admin = actingAsAdmin();
    $target = User::factory()->create();
    $target->ban('Spam', $admin);

    $this->postJson("/api/app/users/{$target->id}/unban")
        ->assertOk()
        ->assertJsonPath('message', 'Ban was lifted');

    $ban = $target->bans()->first();
    expect($target->isBanned())->toBeFalse()
        ->and($ban->lifted_by)->toBe($admin->id)
        ->and($ban->lifted_at)->not->toBeNull();
});

it('returns a conflict when unbanning a user who is not banned', function () {
    actingAsAdmin();
    $target = User::factory()->create();

    $this->postJson("/api/app/users/{$target->id}/unban")
        ->assertConflict();
});
