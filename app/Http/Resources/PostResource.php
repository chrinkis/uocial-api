<?php

namespace App\Http\Resources;

use App\Models\PostComment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userReaction = $this->reactions()
            ->whereBelongsTo(Auth::user())
            ->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'location' => $this->location,
            'is_official' => $this->is_official,
            'body' => $this->body,
            'created_at' => $this->created_at,
            'hashtags' => HashtagResource::collection(
                $this::hashtags()
                    ->get()
            ),
            'reactions' => [
                'user' => $userReaction ? new PostReactionResource($userReaction) : null,
                'total' => [
                    'upvotes' => $this->reactions()
                        ->upvotes()
                        ->count(),
                    'downvotes' => $this->reactions()
                        ->downvotes()
                        ->count(),

                ],
            ],
            'comments' => [
                'total' => $this->comments()
                    ->count(),
                'most_popular' => PostCommentResource::collection(
                    $this->comments()
                        ->whereNull('reply_to')
                        ->withCount([
                            'reactions as upvotes_count' => function (Builder $query) {
                                $query->upvotes();
                            },
                            'reactions as downvotes_count' => function (Builder $query) {
                                $query->downvotes();
                            },
                        ])
                        ->get()
                        ->sortByDesc(function (PostComment $comment) {
                            return $comment->upvotes_count - $comment->downvotes_count;
                        })
                        ->take(3)
                        ->filter(function (PostComment $comment) {
                            return ($comment->upvotes_count - $comment->downvotes_count) > 0;
                        })
                        ->values()
                ),
                'most_recent' => PostCommentResource::collection(
                    $this->comments()
                        ->whereNull('reply_to')
                        ->orderByDesc('created_at')
                        ->limit(3)
                        ->get()
                ),
            ],
            'saved' => $this->savedByUsers()
                ->where('user_id', Auth::user()->id)
                ->exists(),
            'author' => [
                'is_current_user' => Auth::user()->id === $this->user->id,
            ],
            'reported_by_the_user' => $this->reports()
                ->where('user_id', Auth::user()->id)
                ->exists(),
            'moderation' => $this->when(Auth::user()->isModerator(), [
                'is_hidden' => $this->isHidden(),
                'by_system' => $this->isCurrentlyModeratedBySystem(),
                'reports' => [
                    'total' => $this->reports->count(),
                ],
            ]),
        ];
    }
}
