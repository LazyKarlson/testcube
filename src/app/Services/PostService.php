<?php

namespace App\Services;

use App\Contracts\PostRepositoryInterface;
use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class PostService
{
    public function __construct(
        private PostRepositoryInterface $repository
    ) {}

    /**
     * Get paginated posts.
     */
    public function getPaginated(int $perPage, string $sortBy, string $sortOrder): LengthAwarePaginator
    {
        return $this->repository->getPaginated($perPage, $sortBy, $sortOrder);
    }

    /**
     * Find a post by ID.
     */
    public function find(int $id): ?Post
    {
        return $this->repository->find($id);
    }

    /**
     * Search posts.
     */
    public function search(array $criteria): LengthAwarePaginator
    {
        return $this->repository->search($criteria);
    }

    /**
     * Create a new post.
     */
    public function create(User $author, array $data): Post
    {
        // Prepare data with business rules
        $data = $this->preparePostData($data);

        // Add author ID
        $data['author_id'] = $author->id;

        return $this->repository->create($data);
    }

    /**
     * Update an existing post.
     */
    public function update(Post $post, array $data): Post
    {
        // Prepare data with business rules
        $data = $this->preparePostData($data, $post);

        return $this->repository->update($post, $data);
    }

    /**
     * Delete a post.
     */
    public function delete(Post $post): bool
    {
        return $this->repository->delete($post);
    }

    /**
     * Get posts by author.
     */
    public function getByAuthor(int $authorId, int $perPage): LengthAwarePaginator
    {
        return $this->repository->getByAuthor($authorId, $perPage);
    }

    /**
     * Prepare post data with business rules.
     */
    private function preparePostData(array $data, ?Post $post = null): array
    {
        // Auto-set published_at if status is published
        if (isset($data['status']) && $data['status'] === 'published') {
            if (! isset($data['published_at']) && (! $post || ! $post->isPublished())) {
                $data['published_at'] = now();
            }
        }

        // Clear published_at when changing to draft
        if (isset($data['status']) && $data['status'] === 'draft') {
            $data['published_at'] = null;
        }

        // Default status to draft
        if (! isset($data['status']) && ! $post) {
            $data['status'] = 'draft';
        }

        return $data;
    }
}
