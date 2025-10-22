<?php

namespace App\Repositories;

use App\Contracts\PostRepositoryInterface;
use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

class PostRepository implements PostRepositoryInterface
{
    /**
     * Find a post by ID.
     */
    public function find(int $id): ?Post
    {
        return Post::with(['author:id,name,email', 'comments.author:id,name,email'])
            ->find($id);
    }

    /**
     * Get paginated posts.
     */
    public function getPaginated(int $perPage, string $sortBy, string $sortOrder): LengthAwarePaginator
    {
        return Post::with([
            'author:id,name,email',
            'comments' => fn ($q) => $q->latest()->limit(1)->with('author:id,name'),
        ])
            ->withCount('comments')
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }

    /**
     * Search posts with criteria.
     */
    public function search(array $criteria): LengthAwarePaginator
    {
        $query = Post::query();

        // Search query
        if (isset($criteria['q'])) {
            $query->where(function ($q) use ($criteria) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($criteria['q']).'%'])
                    ->orWhereRaw('LOWER(body) LIKE ?', ['%'.strtolower($criteria['q']).'%']);
            });
        }

        // Status filter
        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        // Date range filters
        if (isset($criteria['published_from'])) {
            $query->whereDate('published_at', '>=', $criteria['published_from']);
        }

        if (isset($criteria['published_to'])) {
            $query->whereDate('published_at', '<=', $criteria['published_to']);
        }

        // Load relationships
        $query->with([
            'author:id,name,email',
            'comments' => fn ($q) => $q->latest()->limit(1)->with('author:id,name'),
        ])->withCount('comments');

        // Sorting
        $sortBy = $criteria['sort_by'] ?? 'published_at';
        $sortOrder = $criteria['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $criteria['per_page'] ?? 25;

        return $query->paginate($perPage);
    }

    /**
     * Create a new post.
     */
    public function create(array $data): Post
    {
        return Post::create($data);
    }

    /**
     * Update an existing post.
     */
    public function update(Post $post, array $data): Post
    {
        $post->update($data);

        return $post->fresh();
    }

    /**
     * Delete a post.
     */
    public function delete(Post $post): bool
    {
        return $post->delete();
    }

    /**
     * Get posts by author.
     */
    public function getByAuthor(int $authorId, int $perPage): LengthAwarePaginator
    {
        return Post::where('author_id', $authorId)
            ->latest()
            ->paginate($perPage);
    }
}
