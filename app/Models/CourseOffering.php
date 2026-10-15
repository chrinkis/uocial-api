<?php

namespace App\Models;

use App\Enums\Semester;
use App\Models\Concerns\LogsCatalogActivity;
use App\Models\Concerns\RevivesWhenRecreated;
use Database\Factories\CourseOfferingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourseOffering extends Model
{
    /** @use HasFactory<CourseOfferingFactory> */
    use HasFactory;

    use LogsCatalogActivity;
    use RevivesWhenRecreated;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'semester',
        'year',
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
            'year' => 'integer',
        ];
    }

    /**
     * Get the course that owns the offering.
     *
     * @return BelongsTo<Course,$this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the professors teaching the offering.
     *
     * @return BelongsToMany<Professor,$this>
     */
    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class)
            ->withTimestamps();
    }

    /**
     * @return list<list<string>>
     */
    protected static function uniqueKeys(): array
    {
        return [['course_id', 'semester', 'year']];
    }
}
