<?php

namespace App\Repositories;

use App\Contracts\CommentRepositoryInterface;
use App\Models\Comment;
use Illuminate\Pagination\LengthAwarePaginator;

class CommentRepository implements CommentRepositoryInterface
{
    /**
     * Find a comment by ID.
     */
    public function find(int $id): ?Comment
    {
        return Comment::with('author:id,name,email')->find($id);
    }

    /**
     * Get comments for a post.
     */
    public function getByPost(int $postId, int $perPage): LengthAwarePaginator
    {
        return Comment::where('post_id', $postId)
            ->with('author:id,name,email')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new comment.
     */
    public function create(array $data): Comment
    {
        return Comment::create($data);
    }

    /**
     * Update an existing comment.
     */
    public function update(Comment $comment, array $data): Comment
    {
        $comment->update($data);

        return $comment->fresh();
    }

    /**
     * Delete a comment.
     */
    public function delete(Comment $comment): bool
    {
        return $comment->delete();
    }
}
