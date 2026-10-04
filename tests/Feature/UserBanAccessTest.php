<?php

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
use App\Models\PrivacyPolicy;
use App\Models\TermsOfUse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.altcha.hmac_key' => 'test-hmac-key',
        'sanctum.stateful' => ['localhost'],
    ]);
});

function altchaSolutionForTests(): string
{
    $altcha = new Altcha(hmacSignatureSecret: 'test-hmac-key');
    $algorithm = new Pbkdf2;

    $challenge = $altcha->createChallenge(new CreateChallengeOptions(
        algorithm: $algorithm,
        cost: 1,
    ));
    $solution = $altcha->solveChallenge(new SolveChallengeOptions(
        challenge: $challenge,
        algorithm: $algorithm,
    ));

    return (new Payload($challenge, $solution))->toBase64();
}

function loginFromStatefulFrontend(array $credentials): TestResponse
{
    return test()->withHeader('Origin', 'http://localhost')
        ->postJson('/api/auth/login', $credentials);
}

function acceptLegalDocumentsForBanTest(User $user): void
{
    $privacyPolicy = PrivacyPolicy::latest('id')->first() ?? PrivacyPolicy::factory()->create();
    $termsOfUse = TermsOfUse::latest('id')->first() ?? TermsOfUse::factory()->create();

    $user->acceptedPrivacyPolicies()->attach($privacyPolicy->id);
    $user->acceptedTermsOfUses()->attach($termsOfUse->id);
}

it('lets a banned user log in and returns their active ban', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $ban = $user->ban('Spam', $admin);

    loginFromStatefulFrontend([
        'email' => $user->email,
        'password' => 'password',
        'remember' => false,
        'altcha' => altchaSolutionForTests(),
    ])
        ->assertOk()
        ->assertJsonPath('user.active_ban.id', $ban->id)
        ->assertJsonPath('user.active_ban.reason', 'Spam')
        ->assertJsonMissingPath('user.active_ban.banned_by')
        ->assertJsonMissingPath('user.active_ban.admin');
});

it('lets a user without a ban log in with no active ban', function () {
    $user = User::factory()->create();

    loginFromStatefulFrontend([
        'email' => $user->email,
        'password' => 'password',
        'remember' => false,
        'altcha' => altchaSolutionForTests(),
    ])
        ->assertOk()
        ->assertJsonPath('user.active_ban', null);
});

it('lets a banned user create a new token and returns their active ban', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $ban = $user->ban('Spam', $admin);

    $this->postJson('/api/auth/token/generate', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('user.active_ban.id', $ban->id)
        ->assertJsonPath('user.active_ban.reason', 'Spam')
        ->assertJsonMissingPath('user.active_ban.banned_by')
        ->assertJsonMissingPath('user.active_ban.admin')
        ->assertJsonMissingPath('user.active_ban.admin');

    expect($user->tokens()->count())->toBe(1);
});

it('lets a banned user with an existing token reach /api/user with their active ban', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $token = $user->createToken('device')->plainTextToken;
    $ban = $user->ban('Spam', $admin);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.active_ban.id', $ban->id)
        ->assertJsonPath('data.active_ban.reason', 'Spam')
        ->assertJsonMissingPath('data.active_ban.banned_by');
});

it('blocks a banned user with an existing token on normal app routes', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    acceptLegalDocumentsForBanTest($user);
    $token = $user->createToken('device')->plainTextToken;

    $user->ban('Spam', $admin);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/app/notifications')
        ->assertForbidden()
        ->assertJsonPath('message', 'Your account has been banned.');
});

it('restores app access once the ban is lifted', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    acceptLegalDocumentsForBanTest($user);
    $token = $user->createToken('device')->plainTextToken;

    $user->ban('Spam', $admin);
    $user->unban($admin);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/app/notifications')
        ->assertOk();
});

it('restores app access once the ban expires', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    acceptLegalDocumentsForBanTest($user);
    $token = $user->createToken('device')->plainTextToken;

    $user->ban('Spam', $admin, now()->addMinute());

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/app/notifications')
        ->assertForbidden();

    $this->travel(2)->minutes();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/app/notifications')
        ->assertOk();
});
