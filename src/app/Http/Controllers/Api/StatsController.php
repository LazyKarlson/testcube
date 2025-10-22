<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Get post statistics.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function posts(Request $request)
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

    /**
     * Get comment statistics.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function comments(Request $request)
    {
        // Validate date range parameters
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        // 1. Total number of comments
        $totalComments = Comment::count();

        // 2. Number of comments by date range (if provided)
        $commentsByDateRange = null;
        if ($dateFrom || $dateTo) {
            $query = Comment::query();
            
            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }
            
            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo . ' 23:59:59');
            }
            
            $commentsByDateRange = [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'total' => $query->count(),
            ];
        }

        // 3. Comments activity by hour (0-23)
        $commentsByHour = Comment::select(
                DB::raw('EXTRACT(HOUR FROM created_at) as hour'),
                DB::raw('count(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        // Fill missing hours with 0
        $commentsByHourFilled = [];
        for ($i = 0; $i < 24; $i++) {
            $commentsByHourFilled[$i] = $commentsByHour[$i] ?? 0;
        }

        // 4. Comments activity by weekday (0=Sunday, 6=Saturday)
        $commentsByWeekday = Comment::select(
                DB::raw('EXTRACT(DOW FROM created_at) as weekday'),
                DB::raw('count(*) as count')
            )
            ->groupBy('weekday')
            ->orderBy('weekday')
            ->get()
            ->pluck('count', 'weekday')
            ->toArray();

        // Fill missing weekdays with 0 and add day names
        $weekdayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $commentsByWeekdayFormatted = [];
        for ($i = 0; $i < 7; $i++) {
            $commentsByWeekdayFormatted[] = [
                'day' => $weekdayNames[$i],
                'day_number' => $i,
                'count' => $commentsByWeekday[$i] ?? 0,
            ];
        }

        // 5. Comments activity by date (last 30 days)
        $commentsByDate = Comment::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                ];
            });

        return response()->json([
            'total_comments' => $totalComments,
            'comments_by_date_range' => $commentsByDateRange,
            'activity_by_hour' => $commentsByHourFilled,
            'activity_by_weekday' => $commentsByWeekdayFormatted,
            'activity_by_date_last_30_days' => $commentsByDate,
        ]);
    }

    /**
     * Get user statistics.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function users(Request $request)
    {
        // 1. Number of users by role
        $usersByRole = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->select('roles.name as role', DB::raw('count(*) as count'))
            ->groupBy('roles.name')
            ->get()
            ->pluck('count', 'role')
            ->toArray();

        // Get all roles and fill missing ones with 0
        $allRoles = DB::table('roles')->pluck('name')->toArray();
        $usersByRoleFormatted = [];
        foreach ($allRoles as $role) {
            $usersByRoleFormatted[$role] = $usersByRole[$role] ?? 0;
        }

        // 2. Top 5 authors by posts number
        $topAuthorsByPosts = User::withCount('posts')
            ->with('roles:name')
            ->orderBy('posts_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                    'posts_count' => $user->posts_count,
                ];
            });

        // 3. Top 5 users by comments number
        $topUsersByComments = User::withCount('comments')
            ->with('roles:name')
            ->orderBy('comments_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                    'comments_count' => $user->comments_count,
                ];
            });

        // 4. Total users count
        $totalUsers = User::count();

        // 5. Users without roles
        $usersWithoutRoles = User::doesntHave('roles')->count();

        // 6. Average posts per user
        $avgPostsPerUser = User::withCount('posts')
            ->get()
            ->avg('posts_count');

        // 7. Average comments per user
        $avgCommentsPerUser = User::withCount('comments')
            ->get()
            ->avg('comments_count');

        return response()->json([
            'total_users' => $totalUsers,
            'users_by_role' => $usersByRoleFormatted,
            'users_without_roles' => $usersWithoutRoles,
            'top_authors_by_posts' => $topAuthorsByPosts,
            'top_users_by_comments' => $topUsersByComments,
            'average_posts_per_user' => round($avgPostsPerUser, 2),
            'average_comments_per_user' => round($avgCommentsPerUser, 2),
        ]);
    }
}

