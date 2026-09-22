<?php

namespace App\Enums;

use App\Models\Post;
use App\Models\PostComment;

enum NotificationType: string
{
    case NewCommentToPost = 'newCommentToPost';
    case NewCommentToPostComment = 'newCommentToPostComment';

    case PostHiddenUntilReview = 'postHiddenUntilReview';
    case PostHiddenByModerator = 'postHiddenByModerator';
    case PostUnhiddenByModerator = 'postUnhiddenByModerator';

    case PostCommentHiddenUntilReview = 'postCommentHiddenUntilReview';
    case PostCommentHiddenByModerator = 'postCommentHiddenByModerator';
    case PostCommentUnhiddenByModerator = 'postCommentUnhiddenByModerator';

    public function entityClass(): string
    {
        return match ($this) {
            self::NewCommentToPost,
            self::PostHiddenUntilReview,
            self::PostHiddenByModerator,
            self::PostUnhiddenByModerator => Post::class,
            self::NewCommentToPostComment,
            self::PostCommentHiddenUntilReview,
            self::PostCommentHiddenByModerator,
            self::PostCommentUnhiddenByModerator => PostComment::class,
        };
    }
}
