<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostComment extends Model
{
    /** @use HasFactory<\Database\Factories\PostCommentReactionFactory> */
    use HasFactory;

    /**
     * Get the reactions for the post comment.
     *
     * @return HasMany<PostCommentReaction,$this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(PostCommentReaction::class);
    }
}
