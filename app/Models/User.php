<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Scope a query to only include verified users.
     *
     * @param  Builder<Model>  $query
     */
    #[Scope]
    protected function verified(Builder $query): void
    {
        $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope a query to only include moderators.
     *
     * @param  Builder<Model>  $query
     */
    #[Scope]
    protected function moderators(Builder $query): void
    {
        $query->where('role', UserRole::Moderator)
            ->orWhere('role', UserRole::Admin);
    }

    /**
     * The post that user have saved.
     *
     * @return BelongsToMany<User,$this,Pivot>
     */
    public function savedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'saved_posts');
    }

    /**
     * The posts that user has created.
     *
     * @return HasMany<Post,$this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * The posts reactions of the user.
     *
     * @return HasMany<PostReaction,User>
     */
    public function postReactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    /**
     * Generate a pseudonym for this user within a specific context
     *
     * @param  int|string  $contextId  The ID of the owned resource
     */
    public function getPseudonymFor($contextId): string
    {
        $salt = config('app.pseudonym_salt');

        // If salt has base64: prefix, decode it
        if (str_starts_with($salt, 'base64:')) {
            $salt = base64_decode(substr($salt, 7));
        }

        $data = "{$this->id}:{$contextId}";

        return hash_hmac('sha256', $data, $salt);
    }

    /**
     * The post comments that user has created.
     *
     * @return HasMany<PostComment,$this>
     */
    public function postComments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    /**
     * The postComment reactions of the user.
     *
     * @return HasMany<PostCommentReaction,User>
     */
    public function postCommentReactions(): HasMany
    {
        return $this->hasMany(PostCommentReaction::class);
    }

    /**
     * The post reports that user has created.
     *
     * @return HasMany<PostReport,$this>
     */
    public function postReports(): HasMany
    {
        return $this->hasMany(PostReport::class);
    }

    /**
     * The post comment reports that user has created.
     *
     * @return HasMany<PostCommentReport,$this>
     */
    public function postCommentReports(): HasMany
    {
        return $this->hasMany(PostCommentReport::class);
    }

    public function isModerator(): bool
    {
        return $this->role === UserRole::Admin || $this->role === UserRole::Moderator;
    }

    /**
     * Get the reviews of post reports user has created.
     *
     * @return HasMany<PostReport,User>
     */
    public function postReportReviews(): HasMany
    {
        return $this->hasMany(PostReportReview::class);
    }

    /**
     * Get the moderations of posts user has created.
     *
     * @return HasMany<PostModeration,User>
     */
    public function postModerations(): HasMany
    {
        return $this->hasMany(PostModeration::class);
    }

    /**
     * Get the reviews of post comment reports user has created.
     *
     * @return HasMany<PostCommentReportReview,User>
     */
    public function postCommentReportReviews(): HasMany
    {
        return $this->hasMany(PostCommentReportReview::class);
    }

    /**
     * Get the moderations of post comments user has created.
     *
     * @return HasMany<PostCommentModeration,User>
     */
    public function postCommentModerations(): HasMany
    {
        return $this->hasMany(PostCommentModeration::class);
    }
}
