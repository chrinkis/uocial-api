<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostReport extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'post_id',
        'user_comment',
        'reviewer_notes',
        'review_status',
    ];
}
