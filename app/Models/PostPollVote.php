<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostPollVote extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'option_id',
        'user_id',
    ];

    /**
     * Get the option that was voted for.
     *
     * @return BelongsTo<PostPollOption,$this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(PostPollOption::class, 'option_id');
    }

    /**
     * Get the user that cast the vote.
     *
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
