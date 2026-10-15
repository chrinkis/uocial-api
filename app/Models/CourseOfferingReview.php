<?php

namespace App\Models;

use Database\Factories\CourseOfferingReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseOfferingReview extends Model
{
    /** @use HasFactory<CourseOfferingReviewFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rating',
        'pass_difficulty',
        'excellence_difficulty',
        'concepts_difficulty',
        'time_requirement',
        'lecture_presence_helpfulness',
        'comment',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'pass_difficulty' => 'integer',
            'excellence_difficulty' => 'integer',
            'concepts_difficulty' => 'integer',
            'time_requirement' => 'integer',
            'lecture_presence_helpfulness' => 'integer',
        ];
    }

    /**
     * Get the user that wrote the review.
     *
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course offering that the review is about.
     *
     * @return BelongsTo<CourseOffering,$this>
     */
    public function courseOffering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class);
    }
}
