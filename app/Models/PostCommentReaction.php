<?php

namespace App\Models;

use App\Enums\PostReaction as PostReactionEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostCommentReaction extends Model
{
    /** @use HasFactory<\Database\Factories\PostCommentReactionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'post_comment_id',
        'reaction',
    ];

    /**
     * Get the user that owns the reaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include upvotes.
     *
     * @param  Builder<Model>  $query
     */
    #[Scope]
    protected function upvotes(Builder $query): void
    {
        $query->where('reaction', PostReactionEnum::Upvote);
    }

    /**
     * Scope a query to only include downvotes.
     *
     * @param  Builder<Model>  $query
     */
    #[Scope]
    protected function downvotes(Builder $query): void
    {
        $query->where('reaction', PostReactionEnum::Downvote);
    }
}
