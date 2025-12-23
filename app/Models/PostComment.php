<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostComment extends Model
{
    /** @use HasFactory<\Database\Factories\PostCommentReactionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'comment',
        'reply_to',
        'post_id',
    ];

    /**
     * Get the user that created the comment.
     *
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reactions for the post comment.
     *
     * @return HasMany<PostCommentReaction,$this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(PostCommentReaction::class);
    }

    /**
     * Get the post that the comment belongs to.
     *
     * @return BelongsTo<Post,$this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class)->withoutGlobalScopes();
    }

    /**
     * Get the comment that this comment is replying to.
     *
     * @return BelongsTo<PostComment,$this>
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(PostComment::class, 'reply_to');
    }

    /**
     * Get the replies to this comment.
     *
     * @return HasMany<PostComment,$this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(PostComment::class, 'reply_to');
    }

    /**
     * Get the reports of the comment.
     *
     * @return HasMany<PostCommentReport,$this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(PostCommentReport::class);
    }
}
