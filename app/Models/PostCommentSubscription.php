<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostCommentSubscription extends Model
{
    protected $fillable = [
        'post_comment_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function postComment(): BelongsTo
    {
        return $this->belongsTo(PostComment::class);
    }
}
