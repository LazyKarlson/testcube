<?php

namespace App\Http\Controllers\Api;

use App\Contracts\CacheServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostService;
use App\Transformers\PostTransformer;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        private PostService $postService,
        private PostTransformer $transformer,
        private CacheServiceInterface $cache
    ) {}

    /**
     * Display a listing of posts with pagination and sorting
     *
     * Query parameters:
     * - sort_by: 'published_at' (default), 'title', 'created_at'
     * - sort_order: 'desc' (default), 'asc'
     * - page: page number (default: 1)
     * - per_page: items per page (default: 25, max: 100)
     */
    public function index(Request $request)
    {
        // Validate query parameters
        $validated = $request->validate([
            'sort_by' => 'sometimes|in:published_at,title,created_at',
            'sort_order' => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        // Get sorting parameters
        $sortBy = $validated['sort_by'] ?? 'published_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 25;
        $page = $request->input('page', 1);

        // Create cache key from all parameters
        $cacheKey = "api:posts:page:{$page}:sort:{$sortBy}:{$sortOrder}:per_page:{$perPage}";

        // Cache for 5 minutes (300 seconds)
        return $this->cache->remember($cacheKey, 300, function () use ($sortBy, $sortOrder, $perPage) {
            $posts = $this->postService->getPaginated($perPage, $sortBy, $sortOrder);

            // Transform the data
            $posts->setCollection(
                $this->transformer->transformCollection($posts->getCollection())
            );

            return response()->json($posts);
        });
    }

    /**
     * Store a newly created post
     */
    public function store(Request $request)
    {
        $this->authorize('create', Post::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:posts,title',
            'body' => 'required|string',
            'status' => 'sometimes|in:draft,published',
            'published_at' => 'sometimes|nullable|date',
        ]);

        $post = $this->postService->create($request->user(), $validated);

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post->load('author:id,name,email'),
        ], 201);
    }

    /**
     * Display the specified post
     */
    public function show(Post $post)
    {
        $this->authorize('view', $post);

        $cacheKey = "api:post:{$post->id}";

        // Cache for 10 minutes (600 seconds)
        return $this->cache->remember($cacheKey, 600, function () use ($post) {
            $post->load(['author:id,name,email', 'comments.author:id,name,email']);

            return response()->json([
                'post' => $this->transformer->transformWithDetails($post),
            ]);
        });
    }

    /**
     * Update the specified post
     */
    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255|unique:posts,title,'.$post->id,
            'body' => 'sometimes|required|string',
            'status' => 'sometimes|in:draft,published',
            'published_at' => 'sometimes|nullable|date',
        ]);

        $post = $this->postService->update($post, $validated);

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post->load('author:id,name,email'),
        ]);
    }

    /**
     * Remove the specified post
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $this->postService->delete($post);

        return response()->json([
            'message' => 'Post deleted successfully',
        ]);
    }

    /**
     * Get current user's posts
     */
    public function myPosts(Request $request)
    {
        $posts = $this->postService->getByAuthor($request->user()->id, 15);

        // Transform the data
        $posts->setCollection(
            $this->transformer->transformCollection($posts->getCollection())
        );

        return response()->json($posts);
    }

    /**
     * Search posts with filters
     *
     * Query parameters:
     * - q: search query (searches in title and body, case-insensitive)
     * - status: filter by status ('draft' or 'published')
     * - published_at[from]: start date for published_at range (Y-m-d format)
     * - published_at[to]: end date for published_at range (Y-m-d format)
     * - sort_by: 'published_at' (default), 'title', 'created_at'
     * - sort_order: 'desc' (default), 'asc'
     * - per_page: items per page (default: 25, max: 100)
     */
    public function search(Request $request)
    {
        // Validate query parameters
        $validated = $request->validate([
            'q' => 'required|string|min:1',
            'status' => 'sometimes|in:draft,published',
            'published_at.from' => 'sometimes|date',
            'published_at.to' => 'sometimes|date|after_or_equal:published_at.from',
            'sort_by' => 'sometimes|in:published_at,title,created_at',
            'sort_order' => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        // Prepare search criteria
        $criteria = [
            'q' => $validated['q'],
            'status' => $validated['status'] ?? null,
            'published_from' => $validated['published_at']['from'] ?? null,
            'published_to' => $validated['published_at']['to'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'published_at',
            'sort_order' => $validated['sort_order'] ?? 'desc',
            'per_page' => $validated['per_page'] ?? 25,
        ];

        // Search using service
        $posts = $this->postService->search($criteria);

        // Transform the data
        $posts->setCollection(
            $this->transformer->transformCollection($posts->getCollection())
        );

        return response()->json([
            'query' => $criteria['q'],
            'filters' => [
                'status' => $criteria['status'],
                'published_from' => $criteria['published_from'],
                'published_to' => $criteria['published_to'],
            ],
            'results' => $posts,
        ]);
    }
}
