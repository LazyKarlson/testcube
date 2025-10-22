<?php

namespace App\Contracts;

use App\Models\Comment;
use Illuminate\Pagination\LengthAwarePaginator;

interface CommentRepositoryInterface
{
    /**
     * Find a comment by ID.
     */
    public function find(int $id): ?Comment;

    /**
     * Get comments for a post.
     */
    public function getByPost(int $postId, int $perPage): LengthAwarePaginator;

    /**
     * Create a new comment.
     */
    public function create(array $data): Comment;

    /**
     * Update an existing comment.
     */
    public function update(Comment $comment, array $data): Comment;

    /**
     * Delete a comment.
     */
    public function delete(Comment $comment): bool;
}
