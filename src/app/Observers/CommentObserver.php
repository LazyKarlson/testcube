<?php

namespace App\Observers;

use App\Models\Comment;
use Illuminate\Support\Facades\Cache;

class CommentObserver
{
    /**
     * Handle the Comment "created" event.
     */
    public function created(Comment $comment): void
    {
        $this->clearCommentsCaches($comment);
    }

    /**
     * Handle the Comment "updated" event.
     */
    public function updated(Comment $comment): void
    {
        $this->clearCommentsCaches($comment);
    }

    /**
     * Handle the Comment "deleted" event.
     */
    public function deleted(Comment $comment): void
    {
        $this->clearCommentsCaches($comment);
    }

    /**
     * Clear all comments-related caches
     */
    private function clearCommentsCaches(Comment $comment): void
    {
        // Clear specific post cache (includes comment count and last comment)
        Cache::forget("api:post:{$comment->post_id}");

        // Clear base statistics caches (without date ranges)
        Cache::forget('api:stats:comments');
        Cache::forget('api:stats:posts');
        Cache::forget('api:stats:users');

        // Note: Stats with date ranges expire naturally via TTL (15 minutes)
        // For production with Redis, use: Cache::tags(['comments'])->flush();
    }
}

