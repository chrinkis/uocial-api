<?php

namespace App\Models;

use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'reason',
        'type',
        'entity_id',
        'read',
    ];

    protected $casts = [
        'reason' => NotificationReason::class,
        'type' => NotificationType::class,
        'read' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
