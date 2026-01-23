<?php

namespace App\Http\Resources;

use App\Models\Scopes\NonHiddenPostCommentScope;
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
                'total' => [
                    'upvotes' => $this->reactions()
                        ->upvotes()
                        ->count(),
                    'downvotes' => $this->reactions()
                        ->downvotes()
                        ->count(),
                ],
            ],
            'author' => [
                'pseudonym' => $this->user->getPseudonymFor($this->post_id),
                'is_current_user' => Auth::user()->id === $this->user->id,
                'is_post_author' => $this->user->id === $this->post->user->id,
            ],
            'replies' => [
                'count' => $this->replies->count(),
                'total' => $this->when(Auth::user()->isModerator(), $this->replies()
                    ->withoutGlobalScope(NonHiddenPostCommentScope::class)
                    ->count()),
            ],
            'reported_by_the_user' => $this->reports()
                ->where('user_id', Auth::user()->id)
                ->exists(),
            'moderation' => $this->when(Auth::user()->isModerator(), [
                'is_hidden' => $this->isHidden(),
                'is_auto_hidden' => $this->isAutoHidden(),
                'reports' => [
                    'total' => $this->reports->count(),
                ],
            ]),
            'chain' => $this->getParentIdChain(),
        ];
    }
}
