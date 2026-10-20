<?php

namespace App\Models;

use App\Enums\Semester;
use Database\Factories\CourseOfferingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $year Academic start year (e.g. 2025 for the 2025–26 academic year).
 */
class CourseOffering extends Model
{
    /** @use HasFactory<CourseOfferingFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * course_id is set through the course() relation, not mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'year',
        'semester',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester' => Semester::class,
        ];
    }

    /**
     * The course that is offered.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * The instructors teaching the offering.
     *
     * @return BelongsToMany<Instructor, $this>
     */
    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class);
    }
}
