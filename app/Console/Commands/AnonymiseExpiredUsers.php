<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnonymiseExpiredUsers extends Command
{
    protected $signature = 'app:anonymise-expired-users';

    protected $description = 'Anonymise users deleted more than 5 years ago by removing their personal identifiers';

    public function handle(): int
    {
        $count = 0;

        User::onlyTrashed()
            ->where('deleted_at', '<=', now()->subYears(5))
            ->whereNotNull('email')
            ->chunkById(100, function ($users) use (&$count) {
                foreach ($users as $user) {
                    DB::transaction(function () use ($user) {
                        $user->forceFill([
                            'name' => null,
                            'email' => null,
                            'password' => null,
                            'remember_token' => null,
                        ])->saveQuietly();

                        DB::table('privacy_policy_acceptances')
                            ->where('user_id', $user->id)
                            ->update(['ip_address' => null]);

                        DB::table('terms_of_use_acceptances')
                            ->where('user_id', $user->id)
                            ->update(['ip_address' => null]);
                    });

                    $count++;
                }
            });

        $this->components->info("Anonymised {$count} user(s).");

        return Command::SUCCESS;
    }
}
