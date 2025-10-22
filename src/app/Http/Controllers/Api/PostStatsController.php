<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostStatsController extends Controller
{
    /**
     * Get post statistics.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Validate date range parameters
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        // 1. Number of posts by status
        $postsByStatus = Post::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Ensure both statuses are present
        $postsByStatus = [
            'draft' => $postsByStatus['draft'] ?? 0,
            'published' => $postsByStatus['published'] ?? 0,
        ];

        // 2. Number of posts by date range (if provided)
        $postsByDateRange = null;
        if ($dateFrom || $dateTo) {
            $query = Post::query();
            
            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }
            
            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo . ' 23:59:59');
            }
            
            $postsByDateRange = [
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

        // 3. Average number of comments per post
        $avgCommentsPerPost = Post::withCount('comments')
            ->get()
            ->avg('comments_count');

        // 4. Top 5 most commented posts
        $topCommentedPosts = Post::withCount('comments')
            ->with('author:id,name,email')
            ->orderBy('comments_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($post) {
                return [
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
                ];
            });

        return response()->json([
            'posts_by_status' => $postsByStatus,
            'posts_by_date_range' => $postsByDateRange,
            'average_comments_per_post' => round($avgCommentsPerPost, 2),
            'top_commented_posts' => $topCommentedPosts,
            'total_posts' => Post::count(),
        ]);
    }
}

