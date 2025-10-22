<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Display comments for a post
     */
    public function index(Post $post)
    {
        $comments = $post->comments()->with('user:id,name,email')->latest()->paginate(20);
        
        return response()->json($comments);
    }

    /**
     * Store a newly created comment
     */
    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => $comment->load('user:id,name,email'),
        ], 201);
    }

    /**
     * Display the specified comment
     */
    public function show(Comment $comment)
    {
        return response()->json([
            'comment' => $comment->load(['user:id,name,email', 'post:id,title']),
        ]);
    }

    /**
     * Update the specified comment
     */
    public function update(Request $request, Comment $comment)
    {
        // Check if user owns the comment or is admin
        if ($comment->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. You can only update your own comments.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update($validated);

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $comment->load('user:id,name,email'),
        ]);
    }

    /**
     * Remove the specified comment
     */
    public function destroy(Request $request, Comment $comment)
    {
        // Check if user owns the comment or is admin
        if ($comment->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. You can only delete your own comments.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}

