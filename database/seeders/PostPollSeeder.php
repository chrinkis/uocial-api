<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostPoll;
use App\Models\PostPollOption;
use App\Models\PostPollVote;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostPollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = Post::doesntHave('polls')
            ->inRandomOrder()
            ->limit((int) round(Post::count() * 0.35))
            ->get();

        foreach ($posts as $post) {
            $poll = PostPoll::factory()
                ->for($post)
                ->create();

            $options = [];
            foreach (range(0, rand(1, 4)) as $position) {
                $options[] = PostPollOption::factory()
                    ->poll($poll)
                    ->create([
                        'position' => $position,
                    ]);
            }

            $turnout = rand(40, 90);

            foreach (User::verified()->get() as $user) {
                if (rand(1, 100) > $turnout) {
                    continue;
                }

                $selectedOptions = $poll->allow_multiple_votes
                    ? collect($options)->random(rand(1, count($options)))
                    : collect($options)->random(1);

                foreach ($selectedOptions as $option) {
                    PostPollVote::factory()
                        ->option($option)
                        ->user($user)
                        ->create();
                }
            }
        }
    }
}
