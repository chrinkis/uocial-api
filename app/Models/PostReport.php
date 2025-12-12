<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostReport extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'post_id',
        'comment',
    ];

    /**
     * Get the reviews of the report.
     *
     * @return HasMany<PostCommentReport,$this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(PostReportReview::class);
    }
}
