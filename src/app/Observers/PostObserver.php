<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->clearPostsCaches();
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        // Clear specific post cache
        Cache::forget("api:post:{$post->id}");

        // Clear lists and stats
        $this->clearPostsCaches();
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        // Clear specific post cache
        Cache::forget("api:post:{$post->id}");

        // Clear lists and stats
        $this->clearPostsCaches();
    }

    /**
     * Clear all posts-related caches
     */
    private function clearPostsCaches(): void
    {
        // Clear base statistics cache (without date range)
        Cache::forget('api:stats:posts');
        Cache::forget('api:stats:users');

        // Note: Stats with date ranges have different cache keys (e.g., api:stats:posts:2024-01-01:2024-12-31)
        // These will expire naturally via TTL (15 minutes), which is acceptable

        // Note: Posts list caches (api:posts:page:X:sort:Y:Z:per_page:N) also expire via TTL (5 minutes)
        // Without Redis tags, clearing all variations would require pattern matching which is inefficient

        // For production with Redis, use tags for efficient bulk clearing:
        // Cache::tags(['posts'])->flush();
    }
}
