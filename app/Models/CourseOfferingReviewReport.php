<?php

namespace App\Models;

use Database\Factories\CourseOfferingReviewReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseOfferingReviewReport extends Model
{
    /** @use HasFactory<CourseOfferingReviewReportFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'comment',
    ];

    /**
     * Get the user that created the report.
     *
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reported review.
     *
     * @return BelongsTo<CourseOfferingReview,$this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(CourseOfferingReview::class, 'course_offering_review_id');
    }
}
