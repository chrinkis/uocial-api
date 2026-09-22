<?php

use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PrivacyPolicy;
use App\Models\TermsOfUse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function acceptLegalDocuments(User $user): void
{
    $privacyPolicy = PrivacyPolicy::latest('id')->first() ?? PrivacyPolicy::factory()->create();
    $termsOfUse = TermsOfUse::latest('id')->first() ?? TermsOfUse::factory()->create();

    $user->acceptedPrivacyPolicies()->attach($privacyPolicy->id);
    $user->acceptedTermsOfUses()->attach($termsOfUse->id);
}

function createNotificationFor(User $user, Post|PostComment $entity): Notification
{
    $notification = $user->notifications()->create([
        'reason' => NotificationReason::Owner,
        'type' => $entity instanceof Post
            ? NotificationType::NewModerationToPost
            : NotificationType::NewModerationToPostComment,
        'entity_id' => $entity->id,
    ]);
    $notification->post()->associate($entity instanceof Post ? $entity : $entity->post)->save();

    return $notification;
}

it('lists only the authenticated user notifications', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    acceptLegalDocuments($user);
    acceptLegalDocuments($otherUser);

    $post = Post::factory()->create(['user_id' => $otherUser->id]);
    $ownNotification = createNotificationFor($user, $post);
    createNotificationFor($otherUser, $post);

    Sanctum::actingAs($user);

    $this->getJson('/api/app/notifications')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownNotification->id);
});

it('returns the unread count', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    acceptLegalDocuments($user);

    $post = Post::factory()->create(['user_id' => $otherUser->id]);
    createNotificationFor($user, $post);
    createNotificationFor($user, $post);

    Sanctum::actingAs($user);

    $this->getJson('/api/app/notifications/unread-count')
        ->assertOk()
        ->assertJson(['unread_count' => 2]);
});

it('marks a notification as read', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    acceptLegalDocuments($user);

    $post = Post::factory()->create(['user_id' => $otherUser->id]);
    $notification = createNotificationFor($user, $post);

    Sanctum::actingAs($user);

    $this->postJson("/api/app/notifications/{$notification->id}/read")
        ->assertOk();

    expect($notification->fresh()->read)->toBeTrue();
});

it('does not allow marking another user notification as read', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    acceptLegalDocuments($user);

    $post = Post::factory()->create(['user_id' => $otherUser->id]);
    $otherNotification = createNotificationFor($otherUser, $post);

    Sanctum::actingAs($user);

    $this->postJson("/api/app/notifications/{$otherNotification->id}/read")
        ->assertNotFound();

    expect($otherNotification->fresh()->read)->toBeFalse();
});

it('marks all notifications as read', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    acceptLegalDocuments($user);

    $post = Post::factory()->create(['user_id' => $otherUser->id]);
    createNotificationFor($user, $post);
    createNotificationFor($user, $post);

    $read = createNotificationFor($user, $post);
    $read->update(['read' => true]);

    Sanctum::actingAs($user);

    $this->postJson('/api/app/notifications/read-all')
        ->assertOk();

    expect($user->notifications()->where('read', false)->count())->toBe(0);
});
