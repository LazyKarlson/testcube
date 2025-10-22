<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Transformers\CommentTransformer;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        private CommentTransformer $transformer
    ) {}

    /**
     * Display comments for a post
     */
    public function index(Post $post)
    {
        $comments = Comment::with('author:id,name,email')
            ->where('post_id', $post->id)
            ->latest()
            ->paginate(20);

        // Transform the data
        $comments->setCollection(
            $this->transformer->transformCollection($comments->getCollection())
        );

        return response()->json($comments);
    }

    /**
     * Store a newly created comment
     */
    public function store(Request $request, Post $post)
    {
        $this->authorize('create', Comment::class);

        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $comment = Comment::create([
            'author_id' => $request->user()->id,
            'post_id' => $post->id,
            'body' => $validated['body'],
        ]);

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => $comment->load('author:id,name,email'),
        ], 201);
    }

    /**
     * Display the specified comment
     */
    public function show(Comment $comment)
    {
        $this->authorize('view', $comment);

        return response()->json([
            'comment' => $comment->load(['author:id,name,email', 'post:id,title']),
        ]);
    }

    /**
     * Update the specified comment
     */
    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $comment->update($validated);

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $comment->load('author:id,name,email'),
        ]);
    }

    /**
     * Remove the specified comment
     */
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}
