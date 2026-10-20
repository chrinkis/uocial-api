<?php

namespace App\Models;

use Database\Factories\InstructorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Instructor extends Model
{
    /** @use HasFactory<InstructorFactory> */
    use HasFactory;

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
     * The course offerings taught by the instructor.
     *
     * @return BelongsToMany<CourseOffering, $this>
     */
    public function courseOfferings(): BelongsToMany
    {
        return $this->belongsToMany(CourseOffering::class);
    }
}
