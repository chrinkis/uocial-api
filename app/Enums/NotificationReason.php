<?php

namespace App\Enums;

enum NotificationReason: string
{
    case Owner = 'owner';
    case Follower = 'follower';
}
