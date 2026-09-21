<?php

use App\Enums\ModerationAction;
use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use App\Events\PostCommentModerated;
use App\Events\PostModerated;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('notifies the owner when the post is hidden by the system', function () {
    $owner = User::factory()->create();

    $post = Post::factory()->create(['user_id' => $owner->id]);
    $post->moderations()->create([
        'action' => ModerationAction::Hide,
        'comment' => 'Pending review by a moderator',
    ]);

    PostModerated::dispatch($post);

    $notification = Notification::where('user_id', $owner->id)->first();
    expect($notification)->not->toBeNull()
        ->reason->toBe(NotificationReason::Owner)
        ->type->toBe(NotificationType::NewModerationToPost)
        ->entity_id->toBe($post->id);
});

it('notifies the owner when the post is unhidden by a moderator', function () {
    $owner = User::factory()->create();

    $post = Post::factory()->create(['user_id' => $owner->id]);

    PostModerated::dispatch($post);

    $notification = Notification::where('user_id', $owner->id)->first();
    expect($notification)->not->toBeNull()
        ->reason->toBe(NotificationReason::Owner)
        ->type->toBe(NotificationType::NewModerationToPost)
        ->entity_id->toBe($post->id);
});

it('notifies the owner when the comment is hidden by the system', function () {
    $owner = User::factory()->create();
    $postOwner = User::factory()->create();

    $post = Post::factory()->create(['user_id' => $postOwner->id]);
    $postComment = PostComment::factory()->user($owner)->post($post)->create();
    $postComment->moderations()->create([
        'action' => ModerationAction::Hide,
        'comment' => 'Pending review by a moderator',
    ]);

    PostCommentModerated::dispatch($postComment);

    $notification = Notification::where('user_id', $owner->id)->first();
    expect($notification)->not->toBeNull()
        ->reason->toBe(NotificationReason::Owner)
        ->type->toBe(NotificationType::NewModerationToPostComment)
        ->entity_id->toBe($postComment->id);
});

it('notifies the owner when the comment is unhidden by a moderator', function () {
    $owner = User::factory()->create();
    $postOwner = User::factory()->create();

    $post = Post::factory()->create(['user_id' => $postOwner->id]);
    $postComment = PostComment::factory()->user($owner)->post($post)->create();

    PostCommentModerated::dispatch($postComment);

    $notification = Notification::where('user_id', $owner->id)->first();
    expect($notification)->not->toBeNull()
        ->reason->toBe(NotificationReason::Owner)
        ->type->toBe(NotificationType::NewModerationToPostComment)
        ->entity_id->toBe($postComment->id);
});
