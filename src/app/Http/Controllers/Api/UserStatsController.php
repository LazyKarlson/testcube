<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserStatsController extends Controller
{
    /**
     * Get user statistics.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
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

