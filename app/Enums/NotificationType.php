<?php

namespace App\Enums;

use App\Models\Post;
use App\Models\PostComment;

enum NotificationType: string
{
    case NewCommentToPost = 'newCommentToPost';
    case NewCommentToPostComment = 'newCommentToPostComment';
    case NewModerationToPost = 'newModerationToPost';
    case NewModerationToPostComment = 'newModerationToPostComment';

    public function entityClass(): string
    {
        return match ($this) {
            self::NewCommentToPost,
            self::NewModerationToPost => Post::class,
            self::NewCommentToPostComment,
            self::NewModerationToPostComment => PostComment::class,
        };
    }
}
