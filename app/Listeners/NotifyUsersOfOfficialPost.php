<?php

namespace App\Listeners;

use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use App\Events\PostCreated;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;

class NotifyUsersOfOfficialPost implements ShouldQueue
{
    public function handle(PostCreated $event): void
    {
        $post = $event->post;

        if (! $post->is_official) {
            return;
        }

        User::query()
            ->where('id', '!=', $post->user_id)
            ->select('id')
            ->chunkById(1000, function (Collection $users) use ($post) {
                Notification::insert($users->map(fn (User $user) => [
                    'user_id' => $user->id,
                    'reason' => NotificationReason::Everyone->value,
                    'type' => NotificationType::NewOfficialPost->value,
                    'post_id' => $post->id,
                    'entity_id' => $post->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all());
            });
    }
}
