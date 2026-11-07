<?php

namespace App\Models;

use App\Enums\DataReportReason;
use Database\Factories\CourseReportFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseReport extends Model
{
    /** @use HasFactory<CourseReportFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * The resolved_* attributes are set explicitly when an admin resolves the report.
     *
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'department_id',
        'duplicate_of_id',
        'reason',
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
            'reason' => DataReportReason::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * The user that created the report.
     *
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The reported course, null when the report suggests a new one.
     *
     * @return BelongsTo<Course,$this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * The department a suggested new course should belong to.
     *
     * @return BelongsTo<Department,$this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The course the reported one duplicates.
     *
     * @return BelongsTo<Course,$this>
     */
    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'duplicate_of_id');
    }

    /**
     * The admin that resolved the report.
     *
     * @return BelongsTo<User,$this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Only the reports that have been resolved.
     *
     * @param  Builder<CourseReport>  $query
     */
    #[Scope]
    protected function resolved(Builder $query): void
    {
        $query->whereNotNull('resolved_at');
    }

    /**
     * Only the reports that are still open.
     *
     * @param  Builder<CourseReport>  $query
     */
    #[Scope]
    protected function unresolved(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }
}
