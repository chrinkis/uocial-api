<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostPollOption extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'poll_id',
        'name',
        'position',
    ];

    /**
     * Get the poll that owns the option.
     *
     * @return BelongsTo<PostPoll,$this>
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(PostPoll::class, 'poll_id');
    }

    /**
     * Get the votes for the option.
     *
     * @return HasMany<PostPollVote,$this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PostPollVote::class, 'option_id');
    }
}
