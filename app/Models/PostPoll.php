<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostPoll extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'post_id',
        'allow_multiple_votes',
        'ends_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allow_multiple_votes' => 'boolean',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Get the post that owns the poll.
     *
     * @return BelongsTo<Post,$this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the options for the poll.
     *
     * @return HasMany<PostPollOption,$this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(PostPollOption::class, 'poll_id');
    }
}
