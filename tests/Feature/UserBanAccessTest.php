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

function loginFromStatefulFrontend(array $credentials): TestResponse
{
    return test()->withHeader('Origin', 'http://localhost')
        ->postJson('/api/auth/login', $credentials);
}

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

function acceptLegalDocumentsForBanTest(User $user): void
{
    $privacyPolicy = PrivacyPolicy::latest('id')->first() ?? PrivacyPolicy::factory()->create();
    $termsOfUse = TermsOfUse::latest('id')->first() ?? TermsOfUse::factory()->create();

    $user->acceptedPrivacyPolicies()->attach($privacyPolicy->id);
    $user->acceptedTermsOfUses()->attach($termsOfUse->id);
}

it('blocks a banned user from logging in', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $user->ban('Spam', $admin);

    loginFromStatefulFrontend([
        'email' => $user->email,
        'password' => 'password',
        'remember' => false,
        'altcha' => altchaSolutionForTests(),
    ])
        ->assertForbidden()
        ->assertJsonPath('message', 'Your account has been banned.')
        ->assertJsonPath('reason', 'Spam');

    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('lets a user without a ban log in', function () {
    $user = User::factory()->create();

    loginFromStatefulFrontend([
        'email' => $user->email,
        'password' => 'password',
        'remember' => false,
        'altcha' => altchaSolutionForTests(),
    ])->assertOk();
});

it('blocks a banned user from creating a new token', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $user->ban('Spam', $admin);

    $this->postJson('/api/auth/token/generate', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertForbidden()
        ->assertJsonPath('message', 'Your account has been banned.');

    expect($user->tokens()->count())->toBe(0);
});

it('blocks an existing api token while the user is banned', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $token = $user->createToken('device')->plainTextToken;

    $user->ban('Spam', $admin);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/user')
        ->assertForbidden()
        ->assertJsonPath('message', 'Your account has been banned.');
});

it('blocks an existing api token on app routes while the user is banned', function () {
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

it('restores api token access once the ban is lifted', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $token = $user->createToken('device')->plainTextToken;

    $user->ban('Spam', $admin);
    $user->unban($admin);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/user')
        ->assertOk();
});

it('restores api token access once the ban expires', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $token = $user->createToken('device')->plainTextToken;

    $user->ban('Spam', $admin, now()->addMinute());

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/user')
        ->assertForbidden();

    $this->travel(2)->minutes();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/user')
        ->assertOk();
});
