<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Determine if the user can view any comments.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view comments
        return true;
    }

    /**
     * Determine if the user can view the comment.
     */
    public function view(?User $user, Comment $comment): bool
    {
        // Anyone can view comments
        return true;
    }

    /**
     * Determine if the user can create comments.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_comments');
    }

    /**
     * Determine if the user can update the comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        // Admin can update any comment
        if ($user->isAdmin()) {
            return true;
        }

        // Editor can update any comment
        if ($user->isEditor()) {
            return true;
        }

        // Author can only update own comments
        return $comment->author_id === $user->id && $user->hasPermission('update_comments');
    }

    /**
     * Determine if the user can delete the comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Admin can delete any comment
        if ($user->isAdmin()) {
            return true;
        }

        // Editor can delete any comment
        if ($user->isEditor()) {
            return true;
        }

        // Author can only delete own comments
        return $comment->author_id === $user->id && $user->hasPermission('delete_comments');
    }
}
