<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PostCommentResource extends JsonResource
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
            'post_id' => $this->post_id,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
            'reactions' => [
                'user' => new PostCommentReactionResource(
                    $this->reactions()
                        ->whereBelongsTo(Auth::user())
                        ->first()
                ),
                'upvotes' => $this->reactions()
                    ->upvotes()
                    ->count(),
                'downvotes' => $this->reactions()
                    ->downvotes()
                    ->count(),
            ],
            'author' => [
                'pseudonym' => $this->user->getPseudonymFor($this->id),
                'is_post_author' => Auth::user()->id === $this->user->id,
            ],
            'replies' => [
                'count' => $this->replies->count(),
            ],
        ];
    }
}
