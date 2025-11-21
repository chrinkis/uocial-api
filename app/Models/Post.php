<?php

namespace App\Models;

use App\Models\Scopes\NonHiddenPostScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[ScopedBy([NonHiddenPostScope::class])]
class Post extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'location',
        'body',
    ];

    /**
     * The hashtags that belong to the post.
     *
     * @return BelongsToMany<Hashtag,$this,Pivot>
     */
    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class);
    }

    /**
     * Get the reactions for the post.
     *
     * @return HasMany<PostReaction,$this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    /**
     * Get the comments for the post.
     *
     * @return HasMany<PostComment,$this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    /**
     * The user who have save the post.
     *
     * @return BelongsToMany<User,$this,Pivot>
     */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_posts');
    }

    /**
     * Get the user that created the post.
     *
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reports for the post.
     *
     * @return HasMany<PostReport,$this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(PostReport::class);
    }
}
