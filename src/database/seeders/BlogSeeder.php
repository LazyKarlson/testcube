<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the blog with sample posts and comments.
     */
    public function run(): void
    {
        // Get existing users or create some
        $users = User::all();

        if ($users->count() < 5) {
            // Create additional users if needed
            $users = User::factory()->count(5)->create();
        }

        // Create posts for each user
        $users->each(function ($user) {
            // Each user creates 3-7 posts
            $postCount = rand(3, 7);

            Post::factory()
                ->count($postCount)
                ->forAuthor($user)
                ->create()
                ->each(function ($post) {
                    // Randomly decide if post should be published or draft
                    if (rand(0, 1)) {
                        $post->update([
                            'status' => 'published',
                            'published_at' => now()->subDays(rand(1, 30)),
                        ]);
                    }

                    // Add 2-8 comments to each post
                    $commentCount = rand(2, 8);

                    Comment::factory()
                        ->count($commentCount)
                        ->forPost($post)
                        ->create();
                });
        });

        $this->command->info('Blog seeded successfully!');
        $this->command->info('Created posts: '.Post::count());
        $this->command->info('Created comments: '.Comment::count());
    }
}
