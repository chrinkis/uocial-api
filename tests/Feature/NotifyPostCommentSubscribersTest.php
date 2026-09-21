<?php

use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use App\Events\PostCommentCreated;
use App\Events\PostCreated;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('notifies subscribed users when a comment is created', function () {
    $owner = User::factory()->create();
    $follower = User::factory()->create();
    $commenter = User::factory()->create();

    $post = Post::factory()->create(['user_id' => $owner->id]);
    PostCreated::dispatch($post);
    $follower->postSubscriptions()->create(['post_id' => $post->id]);

    $comment = PostComment::factory()->user($commenter)->post($post)->create();
    PostCommentCreated::dispatch($comment);

    expect(Notification::count())->toBe(2)
        ->and(Notification::where('user_id', $owner->id)->first())
        ->reason->toBe(NotificationReason::Owner)
        ->type->toBe(NotificationType::NewCommentToPost)
        ->entity_id->toBe($post->id)
        ->and(Notification::where('user_id', $follower->id)->first())
        ->reason->toBe(NotificationReason::Follower)
        ->and(Notification::where('user_id', $commenter->id)->exists())->toBeFalse();
});

it('notifies parent comment subscribers for replies', function () {
    $parentOwner = User::factory()->create();
    $postOwner = User::factory()->create();
    $commenter = User::factory()->create();

    $post = Post::factory()->create(['user_id' => $postOwner->id]);
    $parent = PostComment::factory()->user($parentOwner)->post($post)->create();
    $parentOwner->postCommentSubscriptions()->create(['post_comment_id' => $parent->id]);

    $reply = PostComment::factory()->post($post)->replyTo($parent)->create(['user_id' => $commenter->id]);
    PostCommentCreated::dispatch($reply);

    $notification = Notification::where('user_id', $parentOwner->id)->first();
    expect($notification)->not->toBeNull()
        ->type->toBe(NotificationType::NewCommentToPostComment)
        ->entity_id->toBe($parent->id);
});
