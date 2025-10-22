<?php

namespace App\Contracts;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostRepositoryInterface
{
    /**
     * Find a post by ID.
     */
    public function find(int $id): ?Post;

    /**
     * Get paginated posts.
     */
    public function getPaginated(int $perPage, string $sortBy, string $sortOrder): LengthAwarePaginator;

    /**
     * Search posts with criteria.
     */
    public function search(array $criteria): LengthAwarePaginator;

    /**
     * Create a new post.
     */
    public function create(array $data): Post;

    /**
     * Update an existing post.
     */
    public function update(Post $post, array $data): Post;

    /**
     * Delete a post.
     */
    public function delete(Post $post): bool;

    /**
     * Get posts by author.
     */
    public function getByAuthor(int $authorId, int $perPage): LengthAwarePaginator;
}
