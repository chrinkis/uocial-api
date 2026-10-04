<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserBan;
use App\Models\UserBanMessage;
use Database\Seeders\UserBanMessageSeeder;
use Database\Seeders\UserBanSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserBanSeeder::class);
    $this->seed(UserBanMessageSeeder::class);
});

it('creates the banned user with the known password and one permanent active ban', function () {
    $bannedUser = User::firstWhere('email', 'banned@uoc.gr');

    expect(Hash::check('password', $bannedUser->password))->toBeTrue()
        ->and($bannedUser->bans()->count())->toBe(1)
        ->and($bannedUser->bans()->first()->expires_at)->toBeNull()
        ->and($bannedUser->isBanned())->toBeTrue();
});

it('gives the banned user a conversation that is pending review', function () {
    $bannedUser = User::firstWhere('email', 'banned@uoc.gr');
    $ban = $bannedUser->bans()->first();

    expect($ban->messages()->count())->toBe(3)
        ->and($ban->messages()->orderByDesc('id')->first()->sender_id)->toBe($bannedUser->id)
        ->and($ban->unansweredUserMessages())->toBe(1);
});

it('writes conversations where every message is from the banned user or a moderator', function () {
    $messages = UserBanMessage::with('ban')->get();

    expect($messages)->not->toBeEmpty();

    $messages->each(function (UserBanMessage $message) {
        $allowed = [$message->ban->user_id];
        $allowed = [...$allowed, ...User::moderators()->pluck('id')->all()];

        expect(in_array($message->sender_id, $allowed, true))->toBeTrue();
    });
});

it('never bans the other hardcoded users', function () {
    $protected = User::whereIn('email', [
        'unverified@uoc.gr',
        'verified@uoc.gr',
        'regular@uoc.gr',
        'moderator@uoc.gr',
        'admin@uoc.gr',
    ])->pluck('id');

    expect(UserBan::whereIn('user_id', $protected)->count())->toBe(0);
});

it('makes the banned user a regular account', function () {
    expect(User::firstWhere('email', 'banned@uoc.gr')->role)->toBe(UserRole::Regular);
});
