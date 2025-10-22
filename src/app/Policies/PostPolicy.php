<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine if the user can view any posts.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view posts list
        return true;
    }

    /**
     * Determine if the user can view the post.
     */
    public function view(?User $user, Post $post): bool
    {
        // Published posts can be viewed by anyone
        if ($post->isPublished()) {
            return true;
        }

        // Draft posts can only be viewed by author or admin
        return $user && ($post->author_id === $user->id || $user->isAdmin());
    }

    /**
     * Determine if the user can create posts.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_posts');
    }

    /**
     * Determine if the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        // Admin can update any post
        if ($user->isAdmin()) {
            return true;
        }

        // Editor can update any post
        if ($user->isEditor()) {
            return true;
        }

        // Author can only update own posts
        return $post->author_id === $user->id && $user->hasPermission('update_posts');
    }

    /**
     * Determine if the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        // Admin can delete any post
        if ($user->isAdmin()) {
            return true;
        }

        // Editor can delete any post
        if ($user->isEditor()) {
            return true;
        }

        // Author can only delete own posts
        return $post->author_id === $user->id && $user->hasPermission('delete_posts');
    }
}
