<?php

namespace App\Transformers;

use App\Models\Comment;
use Illuminate\Support\Collection;

class CommentTransformer
{
    /**
     * Transform a single comment.
     */
    public function transform(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'post_id' => $comment->post_id,
            'author' => [
                'id' => $comment->author->id,
                'name' => $comment->author->name,
                'email' => $comment->author->email,
            ],
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
        ];
    }

    /**
     * Transform a collection of comments.
     */
    public function transformCollection(Collection $comments): Collection
    {
        return $comments->map(fn ($comment) => $this->transform($comment));
    }
}
