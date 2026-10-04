<?php

use App\Models\User;
use App\Notifications\UserBannedNotification;
use App\Notifications\UserUnbannedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('emails the user when they are banned', function () {
    Notification::fake();
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $user->ban('Spam', $admin, now()->addDays(7));

    Notification::assertSentTo(
        $user,
        UserBannedNotification::class,
        fn (UserBannedNotification $notification) => $notification->ban->reason === 'Spam'
    );
});

it('describes the reason and expiry in the ban email', function () {
    $user = User::factory()->create(['name' => 'Ada']);
    $admin = User::factory()->admin()->create();
    $ban = $user->ban('Spam', $admin, now()->addDays(7));

    $mail = (new UserBannedNotification($ban))->toMail($user);

    expect($mail->subject)->toBe('Your account has been banned')
        ->and($mail->greeting)->toBe('Hello Ada,')
        ->and($mail->introLines)->toContain('Reason: Spam')
        ->and(implode(' ', $mail->introLines))->toContain('The ban expires on')
        ->and(implode(' ', $mail->introLines))->toContain('respond in the app');
});

it('says a ban is permanent when there is no expiry', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $ban = $user->ban('Abuse', $admin);

    $mail = (new UserBannedNotification($ban))->toMail($user);

    expect($mail->introLines)->toContain('This ban is permanent.');
});

it('emails the user when their ban is lifted', function () {
    Notification::fake();
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $user->ban('Spam', $admin);
    $user->unban($admin);

    Notification::assertSentTo($user, UserUnbannedNotification::class);
});

it('does not email when there was no ban to lift', function () {
    Notification::fake();
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $user->unban($admin);

    Notification::assertNothingSent();
});
