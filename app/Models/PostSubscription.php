<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostSubscription extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'post_id',
    ];

    /**
     * Get the user that the subscription reffers to.
     *
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post that the subscription reffers to.
     *
     * @return BelongsTo<Post,$this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
