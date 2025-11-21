<?php

namespace App\Enums;

enum ModerationAction: string
{
    case Hide = 'hide';
    case Unhide = 'unhide';
}
