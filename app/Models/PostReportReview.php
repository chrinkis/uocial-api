<?php

namespace App\Models;

use App\Enums\ReportReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReportReview extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'post_report_id',
        'comment',
        'review_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'review_status' => ReportReviewStatus::class,
        ];
    }

    /**
     * Get the user that owns the post report review.
     *
     * @return BelongsTo<User,PostReportReview>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
