<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserBan;
use Database\Seeders\UserBanSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds bans without touching the hardcoded users', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserBanSeeder::class);

    expect(UserBan::count())->toBeGreaterThan(0);

    $protected = User::whereIn('email', [
        'unverified@uoc.gr',
        'verified@uoc.gr',
        'regular@uoc.gr',
        'moderator@uoc.gr',
        'admin@uoc.gr',
    ])->pluck('id');

    expect(UserBan::whereIn('user_id', $protected)->count())->toBe(0);
});

it('issues every seeded ban from a moderator or admin', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserBanSeeder::class);

    $issuers = UserBan::with('bannedBy')->get()->pluck('bannedBy.role')->unique()->values();

    expect($issuers->every(fn ($role) => in_array($role, [UserRole::Moderator, UserRole::Admin], true)))->toBeTrue();
});

it('seeds a mix of active, expired and lifted bans', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserBanSeeder::class);

    expect(UserBan::active()->count())->toBeGreaterThan(0)
        ->and(UserBan::whereNotNull('lifted_at')->whereNotNull('lifted_by')->count())->toBeGreaterThan(0)
        ->and(UserBan::whereNull('lifted_at')->where('expires_at', '<', now())->count())->toBeGreaterThan(0);
});
