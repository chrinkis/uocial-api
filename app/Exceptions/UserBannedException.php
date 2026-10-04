<?php

namespace App\Exceptions;

use App\Models\UserBan;
use Exception;

class UserBannedException extends Exception
{
    public function __construct(public readonly UserBan $ban)
    {
        parent::__construct('User is banned.');
    }
}
