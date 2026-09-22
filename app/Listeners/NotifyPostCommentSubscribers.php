<?php

namespace App\Listeners;

use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use App\Events\PostCommentCreated;
use App\Models\Notification;
use App\Models\PostCommentSubscription;
use App\Models\PostSubscription;
use Illuminate\Support\Collection;

class NotifyPostCommentSubscribers
{
    public function handle(PostCommentCreated $event): void
    {
        $comment = $event->postComment;

        $rows = $this->buildRows(
            $comment->post->subscriptions()
                ->where('user_id', '!=', $comment->user_id)
                ->get(),
            NotificationType::NewCommentToPost,
            $comment->post_id,
            $comment->post_id,
            $comment->post->user_id,
        );

        if ($comment->reply_to !== null) {
            $parentComment = $comment->replyTo;

            $rows = $rows->concat($this->buildRows(
                $parentComment->subscriptions()
                    ->where('user_id', '!=', $comment->user_id)
                    ->get(),
                NotificationType::NewCommentToPostComment,
                $comment->post_id,
                $parentComment->id,
                $parentComment->user_id,
            ));
        }

        if ($rows->isNotEmpty()) {
            Notification::insert($rows->all());
        }
    }

    /**
     * @param  Collection<int, PostSubscription|PostCommentSubscription>  $subscriptions
     * @return Collection<int, array<string, mixed>>
     */
    private function buildRows(Collection $subscriptions, NotificationType $type, int $postId, int $entityId, int $ownerId): Collection
    {
        return $subscriptions->map(fn ($subscription) => [
            'user_id' => $subscription->user_id,
            'reason' => $subscription->user_id === $ownerId
                ? NotificationReason::Owner->value
                : NotificationReason::Follower->value,
            'type' => $type->value,
            'post_id' => $postId,
            'entity_id' => $entityId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
