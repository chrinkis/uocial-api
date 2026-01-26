<?php

namespace App\Enums;

enum NotificationType: string
{
    case NewCommentToPost = 'newCommentToPost';
    case NewCommentToPostComment = 'newCommentToPostComment';
    case NewModerationToPost = 'newModerationToPost';
    case NewModerationToPostComment = 'newModerationToPostComment';
}
