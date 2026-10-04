<?php

use App\Models\User;
use App\Models\UserBan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('bans a user with a reason and moderator', function () {
    $user = User::factory()->create();
    $moderator = User::factory()->moderator()->create();

    $ban = $user->ban('Spam', $moderator, now()->addDays(7), 'First offence');

    expect($ban)->toBeInstanceOf(UserBan::class)
        ->user_id->toBe($user->id)
        ->banned_by->toBe($moderator->id)
        ->reason->toBe('Spam')
        ->notes->toBe('First offence')
        ->lifted_at->toBeNull();
    expect($user->isBanned())->toBeTrue();
});

it('treats a permanent ban as active', function () {
    $user = User::factory()->create();
    $moderator = User::factory()->moderator()->create();

    $user->ban('Abuse', $moderator);

    expect($user->isBanned())->toBeTrue();
});

it('does not count expired bans as active', function () {
    $user = User::factory()->create();
    $moderator = User::factory()->moderator()->create();

    $user->ban('Old', $moderator, now()->subDay());

    expect($user->isBanned())->toBeFalse();
});

it('lifts active bans on unban and records who lifted them', function () {
    $user = User::factory()->create();
    $moderator = User::factory()->moderator()->create();
    $admin = User::factory()->admin()->create();

    $user->ban('Spam', $moderator);
    $user->unban($admin);

    $ban = $user->bans()->first();
    expect($user->isBanned())->toBeFalse()
        ->and($ban->lifted_at)->not->toBeNull()
        ->and($ban->lifted_by)->toBe($admin->id)
        ->and($ban->liftedBy->is($admin))->toBeTrue();
});
