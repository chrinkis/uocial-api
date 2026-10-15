<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    use LogsCatalogActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'course_prefix',
    ];

    /**
     * Get the courses of the department.
     *
     * @return HasMany<Course,$this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Get the professors of the department.
     *
     * @return BelongsToMany<Professor,$this>
     */
    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class)
            ->withTimestamps();
    }
}
