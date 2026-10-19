<?php

namespace App\Models;

use App\Enums\ModerationAction;
use Database\Factories\CourseOfferingReviewModerationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseOfferingReviewModeration extends Model
{
    /** @use HasFactory<CourseOfferingReviewModerationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'comment',
        'action',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ModerationAction::class,
        ];
    }

    /**
     * Get the moderated review.
     *
     * @return BelongsTo<CourseOfferingReview,$this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(CourseOfferingReview::class, 'course_offering_review_id');
    }

    /**
     * Get the moderator that applied the action.
     *
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
