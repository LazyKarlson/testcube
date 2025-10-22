<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['author_id', 'title', 'body', 'status', 'published_at'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Get the author (user) that owns the post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Alias for author relationship (for backwards compatibility).
     */
    public function user(): BelongsTo
    {
        return $this->author();
    }

    /**
     * Get the comments for the post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Check if the post is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if the post is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Publish the post.
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Unpublish the post (set to draft).
     */
    public function unpublish(): void
    {
        $this->update([
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Create a post with business rules applied.
     */
    public static function createWithDefaults(User $author, array $data): self
    {
        // Auto-set published_at if status is published
        if (($data['status'] ?? 'draft') === 'published' && ! isset($data['published_at'])) {
            $data['published_at'] = now();
        }

        // Clear published_at when draft
        if (($data['status'] ?? 'draft') === 'draft') {
            $data['published_at'] = null;
        }

        // Default status to draft
        $data['status'] = $data['status'] ?? 'draft';
        $data['author_id'] = $author->id;

        return self::create($data);
    }

    /**
     * Update post with business rules applied.
     */
    public function updateWithDefaults(array $data): bool
    {
        // Auto-set published_at when changing to published
        if (isset($data['status']) && $data['status'] === 'published' && ! $this->isPublished()) {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        // Clear published_at when changing to draft
        if (isset($data['status']) && $data['status'] === 'draft') {
            $data['published_at'] = null;
        }

        return $this->update($data);
    }
}
