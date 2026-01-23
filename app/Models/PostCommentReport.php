<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCommentReport extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'post_comment_id',
        'comment',
    ];

    /**
     * Get the reviews of the report.
     *
     * @return HasMany<PostCommentReport,$this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(PostCommentReportReview::class);
    }

    /**
     * Get the post-comment that reffers to.
     *
     * @return BelongsTo<PostComment,$this>
     */
    public function postComment(): BelongsTo
    {
        return $this->belongsTo(PostComment::class)
            ->withoutGlobalScopes();
    }
}
