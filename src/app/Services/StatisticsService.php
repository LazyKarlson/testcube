<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * Get post statistics.
     */
    public function getPostStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        return [
            'posts_by_status' => $this->getPostsByStatus(),
            'posts_by_date_range' => $this->getPostsByDateRange($dateFrom, $dateTo),
            'average_comments_per_post' => $this->getAverageCommentsPerPost(),
            'top_commented_posts' => $this->getTopCommentedPosts(),
            'total_posts' => Post::count(),
        ];
    }

    /**
     * Get comment statistics.
     */
    public function getCommentStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        return [
            'total_comments' => Comment::count(),
            'comments_by_date_range' => $this->getCommentsByDateRange($dateFrom, $dateTo),
            'comments_by_hour' => $this->getCommentsByHour(),
            'comments_by_day_of_week' => $this->getCommentsByDayOfWeek(),
            'top_commenters' => $this->getTopCommenters(),
            'most_commented_posts' => $this->getMostCommentedPosts(),
        ];
    }

    /**
     * Get user statistics.
     */
    public function getUserStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        return [
            'total_users' => User::count(),
            'users_by_date_range' => $this->getUsersByDateRange($dateFrom, $dateTo),
            'users_by_role' => $this->getUsersByRole(),
            'email_verified_users' => $this->getEmailVerifiedUsers(),
            'top_authors' => $this->getTopAuthors(),
        ];
    }

    // ==================== POST STATISTICS ====================

    /**
     * Get posts grouped by status.
     */
    private function getPostsByStatus(): array
    {
        $postsByStatus = Post::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'draft' => $postsByStatus['draft'] ?? 0,
            'published' => $postsByStatus['published'] ?? 0,
        ];
    }

    /**
     * Get posts by date range.
     */
    private function getPostsByDateRange(?string $dateFrom, ?string $dateTo): ?array
    {
        if (! $dateFrom && ! $dateTo) {
            return null;
        }

        $query = Post::query();

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo.' 23:59:59');
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total' => $query->count(),
            'by_status' => $query->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray(),
        ];
    }

    /**
     * Get average comments per post.
     */
    private function getAverageCommentsPerPost(): float
    {
        return round(
            Post::withCount('comments')->get()->avg('comments_count') ?? 0,
            2
        );
    }

    /**
     * Get top commented posts.
     */
    private function getTopCommentedPosts(): array
    {
        return Post::withCount('comments')
            ->with('author:id,name,email')
            ->orderBy('comments_count', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->status,
                'author' => [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                    'email' => $post->author->email,
                ],
                'comments_count' => $post->comments_count,
                'created_at' => $post->created_at->toISOString(),
                'published_at' => $post->published_at?->toISOString(),
            ])
            ->toArray();
    }

    // ==================== COMMENT STATISTICS ====================

    /**
     * Get comments by date range.
     */
    private function getCommentsByDateRange(?string $dateFrom, ?string $dateTo): ?array
    {
        if (! $dateFrom && ! $dateTo) {
            return null;
        }

        $query = Comment::query();

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo.' 23:59:59');
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total' => $query->count(),
        ];
    }

    /**
     * Get comments grouped by hour.
     */
    private function getCommentsByHour(): array
    {
        return Comment::select(DB::raw('EXTRACT(HOUR FROM created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();
    }

    /**
     * Get comments grouped by day of week.
     */
    private function getCommentsByDayOfWeek(): array
    {
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $commentsByDay = Comment::select(DB::raw('EXTRACT(DOW FROM created_at) as day'), DB::raw('count(*) as count'))
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('count', 'day')
            ->toArray();

        $result = [];
        foreach ($commentsByDay as $day => $count) {
            $result[$dayNames[$day]] = $count;
        }

        return $result;
    }

    /**
     * Get top commenters.
     */
    private function getTopCommenters(): array
    {
        return Comment::select('author_id', DB::raw('count(*) as comments_count'))
            ->groupBy('author_id')
            ->orderBy('comments_count', 'desc')
            ->limit(5)
            ->with('author:id,name,email')
            ->get()
            ->map(fn ($comment) => [
                'author' => [
                    'id' => $comment->author->id,
                    'name' => $comment->author->name,
                    'email' => $comment->author->email,
                ],
                'comments_count' => $comment->comments_count,
            ])
            ->toArray();
    }

    /**
     * Get most commented posts.
     */
    private function getMostCommentedPosts(): array
    {
        return Comment::select('post_id', DB::raw('count(*) as comments_count'))
            ->groupBy('post_id')
            ->orderBy('comments_count', 'desc')
            ->limit(5)
            ->with('post:id,title,status')
            ->get()
            ->map(fn ($comment) => [
                'post' => [
                    'id' => $comment->post->id,
                    'title' => $comment->post->title,
                    'status' => $comment->post->status,
                ],
                'comments_count' => $comment->comments_count,
            ])
            ->toArray();
    }

    // ==================== USER STATISTICS ====================

    /**
     * Get users by date range.
     */
    private function getUsersByDateRange(?string $dateFrom, ?string $dateTo): ?array
    {
        if (! $dateFrom && ! $dateTo) {
            return null;
        }

        $query = User::query();

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo.' 23:59:59');
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total' => $query->count(),
        ];
    }

    /**
     * Get users grouped by role.
     */
    private function getUsersByRole(): array
    {
        return DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('count(*) as count'))
            ->groupBy('roles.name')
            ->get()
            ->pluck('count', 'name')
            ->toArray();
    }

    /**
     * Get email verification statistics.
     */
    private function getEmailVerifiedUsers(): array
    {
        $verified = User::whereNotNull('email_verified_at')->count();
        $unverified = User::whereNull('email_verified_at')->count();

        return [
            'verified' => $verified,
            'unverified' => $unverified,
            'total' => $verified + $unverified,
        ];
    }

    /**
     * Get top authors by post count.
     */
    private function getTopAuthors(): array
    {
        return User::withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'posts_count' => $user->posts_count,
            ])
            ->toArray();
    }
}
