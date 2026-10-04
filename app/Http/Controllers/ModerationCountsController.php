<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\Scopes\NonHiddenPostCommentScope;
use App\Models\Scopes\NonHiddenPostScope;
use App\Models\UserBan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ModerationCountsController extends Controller
{
    /**
     * Display the number of items waiting on moderators, and on admins for ban threads.
     *
     * Hidden posts and comments are included, since the items pending review are hidden by definition.
     */
    public function show(): JsonResponse
    {
        $info = [
            'posts_pending_review' => Post::withoutGlobalScope(NonHiddenPostScope::class)
                ->pendingReview()
                ->count(),
            'post_comments_pending_review' => PostComment::withoutGlobalScope(NonHiddenPostCommentScope::class)
                ->pendingReview()
                ->count(),
            'posts_pending_reports' => Post::withoutGlobalScope(NonHiddenPostScope::class)
                ->pendingReports()
                ->count(),
            'post_comments_pending_reports' => PostComment::withoutGlobalScope(NonHiddenPostCommentScope::class)
                ->pendingReports()
                ->count(),
        ];

        if (Auth::user()->isAdmin()) {
            $info['ban_threads_pending_reply'] = UserBan::pendingReview()->count();
        }

        return response()->json($info);
    }
}
