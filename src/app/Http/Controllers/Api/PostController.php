<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    /**
     * Display a listing of posts
     */
    public function index()
    {
        $posts = Post::with('user:id,name,email')->latest()->paginate(15);
        
        return response()->json($posts);
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
        if (isset($validated['status']) && $validated['status'] === 'published' && !isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        // Default status to draft if not provided
        if (!isset($validated['status'])) {
            $validated['status'] = 'draft';
        }

        $post = $request->user()->posts()->create($validated);

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post->load('user:id,name,email'),
        ], 201);
    }

    /**
     * Display the specified post
     */
    public function show(Post $post)
    {
        return response()->json([
            'post' => $post->load(['user:id,name,email', 'comments.user:id,name,email']),
        ]);
    }

    /**
     * Update the specified post
     */
    public function update(Request $request, Post $post)
    {
        // Check if user owns the post or is admin
        if ($post->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. You can only update your own posts.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255|unique:posts,title,' . $post->id,
            'body' => 'sometimes|required|string',
            'status' => 'sometimes|in:draft,published',
            'published_at' => 'sometimes|nullable|date',
        ]);

        // Auto-set published_at when changing status to published
        if (isset($validated['status']) && $validated['status'] === 'published' && !$post->isPublished()) {
            if (!isset($validated['published_at'])) {
                $validated['published_at'] = now();
            }
        }

        // Clear published_at when changing status to draft
        if (isset($validated['status']) && $validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        $post->update($validated);

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post->load('user:id,name,email'),
        ]);
    }

    /**
     * Remove the specified post
     */
    public function destroy(Request $request, Post $post)
    {
        // Check if user owns the post or is admin
        if ($post->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. You can only delete your own posts.',
            ], 403);
        }

        $post->delete();

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
}

