<?php

namespace App\Models;

use Database\Factories\UserBanFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'thread_closed_at' => 'datetime',
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
     * Whether the admins have closed the conversation for this ban.
     */
    public function threadClosed(): bool
    {
        return $this->thread_closed_at !== null;
    }

    /**
     * Close the conversation so no one can post to it.
     */
    public function closeThread(User $closedBy): void
    {
        $this->thread_closed_at = now();
        $this->threadClosedBy()->associate($closedBy);
        $this->save();
    }

    /**
     * Reopen a closed conversation.
     */
    public function reopenThread(): void
    {
        $this->thread_closed_at = null;
        $this->threadClosedBy()->dissociate();
        $this->save();
    }

    /**
     * Number of messages the banned user has sent since the last moderator reply.
     */
    public function unansweredUserMessages(): int
    {
        $lastModeratorMessageId = $this->messages()
            ->where('sender_id', '!=', $this->user_id)
            ->max('id') ?? 0;

        return $this->messages()
            ->where('sender_id', $this->user_id)
            ->where('id', '>', $lastModeratorMessageId)
            ->count();
    }

    /**
     * The messages exchanged in this ban's conversation. Order them where they are listed.
     *
     * @return HasMany<UserBanMessage,$this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(UserBanMessage::class);
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
     * The admin that closed the conversation.
     *
     * @return BelongsTo<User,$this>
     */
    public function threadClosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'thread_closed_by');
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
