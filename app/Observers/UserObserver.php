<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    public function deleted(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        DB::table('saved_posts')->where('user_id', $user->id)->delete();
    }
}
