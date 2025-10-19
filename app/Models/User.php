<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        $query->where('role', UserRole::Moderator);
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
}
