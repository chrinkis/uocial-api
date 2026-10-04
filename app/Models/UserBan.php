<?php

namespace App\Models;

use Database\Factories\UserBanFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBan extends Model
{
    /** @use HasFactory<UserBanFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * banned_by is set through the bannedBy() relation, not mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'reason',
        'banned_at',
        'expires_at',
        'notes',
        'lifted_at',
        'lifted_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'banned_at' => 'datetime',
            'expires_at' => 'datetime',
            'lifted_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to bans that are not lifted and not yet expired.
     *
     * @param  Builder<UserBan>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('lifted_at')
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Whether the ban is in force: not lifted and not yet expired.
     */
    public function isActive(): bool
    {
        return $this->lifted_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * The user that is banned.
     *
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The moderator or admin that issued the ban.
     *
     * @return BelongsTo<User,$this>
     */
    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    /**
     * The moderator or admin that lifted the ban.
     *
     * @return BelongsTo<User,$this>
     */
    public function liftedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }
}
