<?php

namespace App\Models;

use Database\Factories\UserBanMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBanMessage extends Model
{
    /** @use HasFactory<UserBanMessageFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * user_ban_id and sender_id are set through their relations, not mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'body',
    ];

    /**
     * The ban this message belongs to.
     *
     * @return BelongsTo<UserBan,$this>
     */
    public function ban(): BelongsTo
    {
        return $this->belongsTo(UserBan::class, 'user_ban_id');
    }

    /**
     * The account that wrote the message. Never exposed through the API.
     *
     * @return BelongsTo<User,$this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Whether the message was written by the banned user.
     */
    public function isFromBannedUser(): bool
    {
        return $this->sender_id === $this->ban->user_id;
    }
}
