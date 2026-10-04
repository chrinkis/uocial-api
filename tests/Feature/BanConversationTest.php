<?php

use App\Models\User;
use App\Models\UserBan;
use App\Models\UserBanMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function banFor(User $user, ?User $admin = null): UserBan
{
    return $user->ban('Spam', $admin ?? User::factory()->admin()->create());
}

function messageFrom(UserBan $ban, User $sender, string $body = 'hello'): UserBanMessage
{
    $message = $ban->messages()->make(['body' => $body]);
    $message->sender()->associate($sender);
    $message->save();

    return $message;
}

it('lets a banned user read and post on their own ban', function () {
    $user = User::factory()->create();
    $ban = banFor($user);
    Sanctum::actingAs($user);

    $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => 'Please review'])
        ->assertCreated()
        ->assertJsonPath('data.sender', 'user')
        ->assertJsonPath('data.body', 'Please review');

    $this->getJson("/api/app/bans/{$ban->id}/messages")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('does not let a user read or post on someone else\'s ban', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $ban = banFor($user);
    Sanctum::actingAs($other);

    $this->getJson("/api/app/bans/{$ban->id}/messages")->assertForbidden();
    $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => 'Hi'])->assertForbidden();
});

it('shows moderator replies as moderator with no identity', function () {
    $user = User::factory()->create();
    $moderator = User::factory()->moderator()->create();
    $ban = banFor($user);
    Sanctum::actingAs($moderator);

    $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => 'Can you explain?'])
        ->assertCreated()
        ->assertJsonPath('data.sender', 'moderator')
        ->assertJsonMissingPath('data.sender_id');

    $this->getJson("/api/app/bans/{$ban->id}/messages")
        ->assertJsonPath('data.0.sender', 'moderator')
        ->assertJsonMissingPath('data.0.sender_id')
        ->assertJsonMissingPath('data.0.user_id');
});

it('stops a banned user after 3 unanswered messages until a moderator replies', function () {
    $user = User::factory()->create();
    $moderator = User::factory()->moderator()->create();
    $ban = banFor($user);
    Sanctum::actingAs($user);

    foreach (range(1, 3) as $i) {
        $this->travel(2)->minutes();
        $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => "Message {$i}"])->assertCreated();
    }

    $this->travel(2)->minutes();
    $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => 'Message 4'])
        ->assertStatus(429);

    messageFrom($ban, $moderator, 'Reply');

    $this->travel(2)->minutes();
    $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => 'Message 5'])
        ->assertCreated();
});

it('throttles a banned user to one message per minute', function () {
    $user = User::factory()->create();
    $ban = banFor($user);
    Sanctum::actingAs($user);

    $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => 'First'])->assertCreated();
    $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => 'Second'])->assertStatus(429);
});

it('keeps the conversation readable but not writable after the ban is lifted', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $ban = banFor($user, $admin);
    messageFrom($ban, $user, 'Please review');
    $user->unban($admin);
    Sanctum::actingAs($user);

    $this->getJson("/api/app/bans/{$ban->id}/messages")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => 'Still here'])
        ->assertForbidden()
        ->assertJsonPath('message', 'This ban is no longer active.');
});

it('blocks posting once the ban has expired', function () {
    $user = User::factory()->create();
    $ban = $user->ban('Spam', User::factory()->admin()->create(), now()->subDay());
    Sanctum::actingAs($user);

    $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => 'Hello'])
        ->assertForbidden();
});

it('lets an admin close and reopen a thread, blocking posts while closed', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $ban = banFor($user, $admin);

    Sanctum::actingAs($admin);
    $this->postJson("/api/app/bans/{$ban->id}/thread/close")->assertOk();

    Sanctum::actingAs($user);
    $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => 'Hello'])
        ->assertForbidden()
        ->assertJsonPath('message', 'This conversation has been closed.');
    $this->getJson("/api/app/bans/{$ban->id}/messages")->assertOk();

    Sanctum::actingAs($admin);
    $this->postJson("/api/app/bans/{$ban->id}/thread/reopen")->assertOk();

    $this->travel(2)->minutes();
    Sanctum::actingAs($user);
    $this->postJson("/api/app/bans/{$ban->id}/messages", ['body' => 'Back'])->assertCreated();
});

it('does not let a moderator close or reopen a thread', function () {
    $user = User::factory()->create();
    $moderator = User::factory()->moderator()->create();
    $ban = banFor($user);
    Sanctum::actingAs($moderator);

    $this->postJson("/api/app/bans/{$ban->id}/thread/close")->assertForbidden();
});

it('lists ban reviews where the banned user spoke last on an active open thread', function () {
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();

    // Included: active, open, the user spoke last.
    $included = banFor(User::factory()->create(), $admin);
    messageFrom($included, $moderator, 'Why?');
    messageFrom($included, $included->user, 'Mistake');

    // Excluded: a moderator spoke last.
    $moderatorLast = banFor(User::factory()->create(), $admin);
    messageFrom($moderatorLast, $moderatorLast->user, 'Please');
    messageFrom($moderatorLast, $moderator, 'No');

    // Excluded: lifted.
    $lifted = banFor(User::factory()->create(), $admin);
    messageFrom($lifted, $lifted->user, 'Hi');
    $lifted->user->unban($admin);

    // Excluded: thread closed.
    $closed = banFor(User::factory()->create(), $admin);
    messageFrom($closed, $closed->user, 'Hi');
    $closed->closeThread($admin);

    Sanctum::actingAs($admin);

    $this->getJson('/api/app/ban-reviews')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ban_id', $included->id)
        ->assertJsonPath('data.0.thread_closed', false)
        ->assertJsonCount(2, 'data.0.messages')
        ->assertJsonPath('data.0.messages.0.sender', 'moderator')
        ->assertJsonPath('data.0.messages.1.sender', 'user')
        ->assertJsonMissingPath('data.0.messages.0.sender_id');
});

it('forbids moderators from ban reviews', function () {
    Sanctum::actingAs(User::factory()->moderator()->create());

    $this->getJson('/api/app/ban-reviews')->assertForbidden();
});
