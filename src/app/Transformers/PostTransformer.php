<?php

namespace App\Transformers;

use App\Models\Post;
use Illuminate\Support\Collection;

class PostTransformer
{
    /**
     * Transform a single post.
     */
    public function transform(Post $post): array
    {
        $lastComment = $post->comments->first();

        return [
            'id' => $post->id,
            'title' => $post->title,
            'status' => $post->status,
            'body' => $post->body,
            'created_at' => $post->created_at,
            'published_at' => $post->published_at,
            'author' => $this->transformAuthor($post->author),
            'comments_count' => $post->comments_count ?? 0,
            'last_comment' => $lastComment ? $this->transformLastComment($lastComment) : null,
        ];
    }

    /**
     * Transform a collection of posts.
     */
    public function transformCollection(Collection $posts): Collection
    {
        return $posts->map(fn ($post) => $this->transform($post));
    }

    /**
     * Transform post with full details (including all comments).
     */
    public function transformWithDetails(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'status' => $post->status,
            'body' => $post->body,
            'created_at' => $post->created_at,
            'published_at' => $post->published_at,
            'author' => $this->transformAuthor($post->author),
            'comments' => $post->comments->map(fn ($comment) => $this->transformComment($comment)),
        ];
    }

    /**
     * Transform author data.
     */
    private function transformAuthor($author): array
    {
        return [
            'id' => $author->id,
            'name' => $author->name,
            'email' => $author->email,
        ];
    }

    /**
     * Transform last comment data.
     */
    private function transformLastComment($comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'author_name' => $comment->author->name,
            'created_at' => $comment->created_at,
        ];
    }

    /**
     * Transform full comment data.
     */
    private function transformComment($comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'author' => [
                'id' => $comment->author->id,
                'name' => $comment->author->name,
            ],
            'created_at' => $comment->created_at,
        ];
    }
}
