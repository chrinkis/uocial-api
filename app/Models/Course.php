<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use App\Models\Concerns\RevivesWhenRecreated;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    use LogsCatalogActivity;
    use RevivesWhenRecreated;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'department_id',
        'code',
        'name',
    ];

    /**
     * Get the department that owns the course.
     *
     * @return BelongsTo<Department,$this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the offerings of the course.
     *
     * @return HasMany<CourseOffering,$this>
     */
    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    /**
     * @return list<list<string>>
     */
    protected static function uniqueKeys(): array
    {
        return [['department_id', 'code']];
    }
}
