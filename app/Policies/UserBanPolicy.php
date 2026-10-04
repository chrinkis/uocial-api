<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserBan;

class UserBanPolicy
{
    /**
     * Banned users can read their own conversation. Moderators and admins can read any.
     */
    public function view(User $user, UserBan $ban): bool
    {
        return $user->isModerator() || $user->is($ban->user);
    }

    /**
     * Posting is allowed while the ban is active and the thread is open.
     */
    public function reply(User $user, UserBan $ban): bool
    {
        return $this->view($user, $ban)
            && $ban->isActive()
            && ! $ban->threadClosed();
    }

    /**
     * Only admins can close or reopen a conversation.
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
