<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PostController extends Controller
{
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
        return Cache::remember($cacheKey, 300, function () use ($sortBy, $sortOrder, $perPage) {
            // Build query with relationships and counts
            $posts = Post::with([
                'author:id,name,email',
                'comments' => function ($query) {
                    $query->latest()->limit(1)->with('author:id,name');
                },
            ])
                ->withCount('comments')
                ->orderBy($sortBy, $sortOrder)
                ->paginate($perPage);

            // Transform the data to include required fields
            $posts->getCollection()->transform(function ($post) {
                $lastComment = $post->comments->first();

                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'status' => $post->status,
                    'body' => $post->body,
                    'created_at' => $post->created_at,
                    'published_at' => $post->published_at,
                    'author' => [
                        'name' => $post->author->name,
                        'email' => $post->author->email,
                    ],
                    'comments_count' => $post->comments_count,
                    'last_comment' => $lastComment ? [
                        'body' => $lastComment->body,
                        'author_name' => $lastComment->author->name,
                    ] : null,
                ];
            });

            return response()->json($posts);
        });
    }

    /**
     * Store a newly created post
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:posts,title',
            'body' => 'required|string',
            'status' => 'sometimes|in:draft,published',
            'published_at' => 'sometimes|nullable|date',
        ]);

        // Auto-set published_at if status is published and published_at is not provided
        if (isset($validated['status']) && $validated['status'] === 'published' && ! isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        // Default status to draft if not provided
        if (! isset($validated['status'])) {
            $validated['status'] = 'draft';
        }

        $post = $request->user()->posts()->create($validated);

        // Cache will be cleared automatically by PostObserver

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
        $cacheKey = "api:post:{$post->id}";

        // Cache for 10 minutes (600 seconds)
        return Cache::remember($cacheKey, 600, function () use ($post) {
            return response()->json([
                'post' => $post->load(['author:id,name,email', 'comments.author:id,name,email']),
            ]);
        });
    }

    /**
     * Update the specified post
     */
    public function update(Request $request, Post $post)
    {
        // Check if user owns the post or is admin
        if ($post->author_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. You can only update your own posts.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255|unique:posts,title,'.$post->id,
            'body' => 'sometimes|required|string',
            'status' => 'sometimes|in:draft,published',
            'published_at' => 'sometimes|nullable|date',
        ]);

        // Auto-set published_at when changing status to published
        if (isset($validated['status']) && $validated['status'] === 'published' && ! $post->isPublished()) {
            if (! isset($validated['published_at'])) {
                $validated['published_at'] = now();
            }
        }

        // Clear published_at when changing status to draft
        if (isset($validated['status']) && $validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        $post->update($validated);

        // Cache will be cleared automatically by PostObserver

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post->load('author:id,name,email'),
        ]);
    }

    /**
     * Remove the specified post
     */
    public function destroy(Request $request, Post $post)
    {
        // Check if user owns the post or is admin
        if ($post->author_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. You can only delete your own posts.',
            ], 403);
        }

        $post->delete();

        // Cache will be cleared automatically by PostObserver

        return response()->json([
            'message' => 'Post deleted successfully',
        ]);
    }

    /**
     * Get current user's posts
     */
    public function myPosts(Request $request)
    {
        $posts = $request->user()->posts()->latest()->paginate(15);

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

        // Get parameters
        $searchQuery = $validated['q'];
        $status = $validated['status'] ?? null;
        $publishedFrom = $validated['published_at']['from'] ?? null;
        $publishedTo = $validated['published_at']['to'] ?? null;
        $sortBy = $validated['sort_by'] ?? 'published_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 25;

        // Build query
        $query = Post::query();

        // Case-insensitive search in title and body
        $query->where(function ($q) use ($searchQuery) {
            $q->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($searchQuery).'%'])
                ->orWhereRaw('LOWER(body) LIKE ?', ['%'.strtolower($searchQuery).'%']);
        });

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by published_at date range
        if ($publishedFrom) {
            $query->whereDate('published_at', '>=', $publishedFrom);
        }
        if ($publishedTo) {
            $query->whereDate('published_at', '<=', $publishedTo);
        }

        // Load relationships and counts
        $posts = $query->with([
            'author:id,name,email',
            'comments' => function ($query) {
                $query->latest()->limit(1)->with('author:id,name');
            },
        ])
            ->withCount('comments')
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        // Transform the data to include required fields
        $posts->getCollection()->transform(function ($post) {
            $lastComment = $post->comments->first();

            return [
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->status,
                'body' => $post->body,
                'created_at' => $post->created_at,
                'published_at' => $post->published_at,
                'author' => [
                    'name' => $post->author->name,
                    'email' => $post->author->email,
                ],
                'comments_count' => $post->comments_count,
                'last_comment' => $lastComment ? [
                    'body' => $lastComment->body,
                    'author_name' => $lastComment->author->name,
                ] : null,
            ];
        });

        return response()->json([
            'query' => $searchQuery,
            'filters' => [
                'status' => $status,
                'published_from' => $publishedFrom,
                'published_to' => $publishedTo,
            ],
            'results' => $posts,
        ]);
    }
}
