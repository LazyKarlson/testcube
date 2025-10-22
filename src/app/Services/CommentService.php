<?php

namespace App\Services;

use App\Contracts\CommentRepositoryInterface;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class CommentService
{
    public function __construct(
        private CommentRepositoryInterface $repository
    ) {}

    /**
     * Find a comment by ID.
     */
    public function find(int $id): ?Comment
    {
        return $this->repository->find($id);
    }

    /**
     * Get comments for a post.
     */
    public function getByPost(int $postId, int $perPage): LengthAwarePaginator
    {
        return $this->repository->getByPost($postId, $perPage);
    }

    /**
     * Create a new comment.
     */
    public function create(User $author, int $postId, array $data): Comment
    {
        $data['author_id'] = $author->id;
        $data['post_id'] = $postId;

        return $this->repository->create($data);
    }

    /**
     * Update an existing comment.
     */
    public function update(Comment $comment, array $data): Comment
    {
        return $this->repository->update($comment, $data);
    }

    /**
     * Delete a comment.
     */
    public function delete(Comment $comment): bool
    {
        return $this->repository->delete($comment);
    }
}
