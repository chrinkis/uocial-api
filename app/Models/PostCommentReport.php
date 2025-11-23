<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostCommentReport extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'post_comment_id',
        'user_comment',
        'reviewer_notes',
        'review_status',
    ];
}
