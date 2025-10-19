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
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'created_at' => $this->created_at,
            'hashtags' => HashtagResource::collection(
                $this::hashtags()
                    ->get()
            ),
            'labels' => HashtagResource::collection(
                $this::labels()
                    ->get()
            ),
            'reactions' => [
                'user' => new PostReactionResource(
                    $this->reactions()
                        ->whereBelongsTo(Auth::user())
                        ->first()
                ),
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
                        ->orderByDesc('created_at')
                        ->limit(3)
                        ->get()
                ),
            ],
        ];
    }
}
