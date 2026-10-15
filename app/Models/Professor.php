<?php

namespace App\Models;

use Database\Factories\ProfessorFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Professor extends Model
{
    /** @use HasFactory<ProfessorFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
    ];

    /**
     * Get the professor's full name, skipping the middle name if absent.
     *
     * @return Attribute<string,never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])->filter()
            ->implode(' '));
    }

    /**
     * Get the departments the professor belongs to.
     *
     * @return BelongsToMany<Department,$this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)
            ->withTimestamps();
    }

    /**
     * Get the course offerings the professor teaches.
     *
     * @return BelongsToMany<CourseOffering,$this>
     */
    public function courseOfferings(): BelongsToMany
    {
        return $this->belongsToMany(CourseOffering::class)
            ->withTimestamps();
    }
}
