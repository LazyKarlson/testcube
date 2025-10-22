# Caching Implementation Examples

## Quick Start Guide

This document shows practical examples of implementing caching for the most important endpoints.

---

## 1. Statistics Endpoints (Highest Priority)

### Unified Stats Controller

All statistics are now handled by a single `StatsController` with three methods: `posts()`, `comments()`, and `users()`.

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Get post statistics.
     */
    public function posts(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        // Create cache key based on parameters
        $cacheKey = 'api:stats:posts';
        if ($dateFrom || $dateTo) {
            $cacheKey .= ':' . ($dateFrom ?? 'null') . ':' . ($dateTo ?? 'null');
        }

        // Cache for 15 minutes (900 seconds)
        return Cache::remember($cacheKey, 900, function () use ($dateFrom, $dateTo) {
            // 1. Number of posts by status
            $postsByStatus = Post::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

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
        });
    }
}
```

---

## 2. Roles Metadata (Very Stable Data)

### Role Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Support\Facades\Cache;

class RoleController extends Controller
{
    /**
     * Get all roles (public endpoint for metadata)
     */
    public function metaRoles()
    {
        // Cache for 1 hour (3600 seconds)
        // Roles rarely change
        return Cache::remember('api:meta:roles', 3600, function () {
            $roles = Role::with('permissions')->get();
            
            return response()->json([
                'roles' => $roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                        'permissions' => $role->permissions->pluck('name'),
                    ];
                }),
            ]);
        });
    }

    /**
     * Assign role to user (admin only)
     */
    public function assignRole(Request $request, User $user)
    {
        // ... assign role logic ...

        // Invalidate roles cache when roles change
        Cache::forget('api:meta:roles');

        return response()->json(['message' => 'Role assigned successfully']);
    }

    /**
     * Remove role from user (admin only)
     */
    public function removeRole(Request $request, User $user)
    {
        // ... remove role logic ...

        // Invalidate roles cache when roles change
        Cache::forget('api:meta:roles');

        return response()->json(['message' => 'Role removed successfully']);
    }
}
```

---

## 3. Posts List (Frequently Accessed)

### Post Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'sort_by' => 'in:published_at,title,created_at',
            'sort_order' => 'in:asc,desc',
        ]);

        $page = $validated['page'] ?? 1;
        $perPage = $validated['per_page'] ?? 25;
        $sortBy = $validated['sort_by'] ?? 'published_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';

        // Create cache key from all parameters
        $cacheKey = "api:posts:page:{$page}:sort:{$sortBy}:{$sortOrder}:per_page:{$perPage}";

        // Cache for 5 minutes (300 seconds)
        return Cache::remember($cacheKey, 300, function () use ($sortBy, $sortOrder, $perPage) {
            $query = Post::with([
                'author:id,name,email',
                'comments' => function ($query) {
                    $query->latest()->limit(1);
                },
                'comments.author:id,name'
            ])->withCount('comments');

            $query->orderBy($sortBy, $sortOrder);

            $posts = $query->paginate($perPage);

            return response()->json([
                'data' => $posts->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'title' => $post->title,
                        'body' => $post->body,
                        'status' => $post->status,
                        'created_at' => $post->created_at->toISOString(),
                        'published_at' => $post->published_at?->toISOString(),
                        'author' => [
                            'name' => $post->author->name,
                            'email' => $post->author->email,
                        ],
                        'comments_count' => $post->comments_count,
                        'last_comment' => $post->comments->first() ? [
                            'body' => $post->comments->first()->body,
                            'author_name' => $post->comments->first()->author->name,
                        ] : null,
                    ];
                }),
                'meta' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                ],
            ]);
        });
    }

    public function store(Request $request)
    {
        // ... create post logic ...

        // Invalidate posts list cache
        $this->clearPostsCache();

        return response()->json($post, 201);
    }

    public function update(Request $request, Post $post)
    {
        // ... update post logic ...

        // Invalidate specific post and lists cache
        Cache::forget("api:post:{$post->id}");
        $this->clearPostsCache();

        return response()->json($post);
    }

    public function destroy(Post $post)
    {
        // ... delete post logic ...

        // Invalidate specific post and lists cache
        Cache::forget("api:post:{$post->id}");
        $this->clearPostsCache();

        return response()->json(null, 204);
    }

    /**
     * Clear all posts list caches
     */
    private function clearPostsCache()
    {
        // If using Redis with tags support
        if (config('cache.default') === 'redis') {
            Cache::tags(['posts'])->flush();
        } else {
            // Clear statistics cache
            Cache::forget('api:stats:posts');
            
            // You could also use a pattern to clear all post list caches
            // This requires additional implementation
        }
    }
}
```

---

## 4. Cache Observers (Automatic Invalidation)

### Post Observer

```php
<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->clearPostsCaches();
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        // Clear specific post cache
        Cache::forget("api:post:{$post->id}");
        
        // Clear lists and stats
        $this->clearPostsCaches();
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        // Clear specific post cache
        Cache::forget("api:post:{$post->id}");
        
        // Clear lists and stats
        $this->clearPostsCaches();
    }

    /**
     * Clear all posts-related caches
     */
    private function clearPostsCaches(): void
    {
        // Clear statistics
        Cache::forget('api:stats:posts');
        
        // If using Redis with tags
        if (config('cache.default') === 'redis') {
            Cache::tags(['posts'])->flush();
        }
    }
}
```

### Comment Observer

```php
<?php

namespace App\Observers;

use App\Models\Comment;
use Illuminate\Support\Facades\Cache;

class CommentObserver
{
    /**
     * Handle the Comment "created" event.
     */
    public function created(Comment $comment): void
    {
        $this->clearCommentsCaches($comment);
    }

    /**
     * Handle the Comment "updated" event.
     */
    public function updated(Comment $comment): void
    {
        $this->clearCommentsCaches($comment);
    }

    /**
     * Handle the Comment "deleted" event.
     */
    public function deleted(Comment $comment): void
    {
        $this->clearCommentsCaches($comment);
    }

    /**
     * Clear all comments-related caches
     */
    private function clearCommentsCaches(Comment $comment): void
    {
        // Clear post cache (includes comment count)
        Cache::forget("api:post:{$comment->post_id}");
        
        // Clear comment statistics
        Cache::forget('api:stats:comments');
        
        // Clear post statistics (includes average comments)
        Cache::forget('api:stats:posts');
    }
}
```

### Register Observers

```php
<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Role;
use App\Observers\CommentObserver;
use App\Observers\PostObserver;
use App\Observers\RoleObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Post::observe(PostObserver::class);
        Comment::observe(CommentObserver::class);
        Role::observe(RoleObserver::class);
    }
}
```

---

## 5. Cache Configuration

### .env Configuration

```env
# Use Redis for production (supports tags)
CACHE_DRIVER=redis

# Redis configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

### config/cache.php

```php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

---

## 6. Testing Cache

### Test Cache is Working

```bash
# First request (cache miss)
time curl -X GET "http://localhost:85/api/stats/posts"
# Should take ~150ms

# Second request (cache hit)
time curl -X GET "http://localhost:85/api/stats/posts"
# Should take ~2-5ms

# Clear cache
php artisan cache:clear

# Third request (cache miss again)
time curl -X GET "http://localhost:85/api/stats/posts"
# Should take ~150ms again
```

### Artisan Commands

```bash
# Clear all cache
php artisan cache:clear

# Clear specific cache key
php artisan tinker
>>> Cache::forget('api:stats:posts')

# Check if key exists
>>> Cache::has('api:stats:posts')

# Get cache value
>>> Cache::get('api:stats:posts')
```

---

## 7. Monitoring Cache Performance

### Add Cache Headers

```php
public function index(Request $request)
{
    $cacheKey = 'api:stats:posts';
    $isCached = Cache::has($cacheKey);
    
    $response = Cache::remember($cacheKey, 900, function () {
        // ... logic
    });
    
    // Add header to indicate cache status
    return response($response)
        ->header('X-Cache-Status', $isCached ? 'HIT' : 'MISS');
}
```

### Check Cache Headers

```bash
curl -I "http://localhost:85/api/stats/posts"
# Look for: X-Cache-Status: HIT or MISS
```

---

## Summary

### Quick Implementation Checklist

1. ✅ Install Redis (if not already)
2. ✅ Update `.env` to use Redis cache driver
3. ✅ Add caching to statistics controllers (15 min TTL)
4. ✅ Add caching to `/api/meta/roles` (1 hour TTL)
5. ✅ Create observers for automatic cache invalidation
6. ✅ Register observers in `AppServiceProvider`
7. ✅ Test cache performance
8. ✅ Monitor cache hit rates

### Expected Results

- **Statistics endpoints**: 99% faster (150ms → 2ms)
- **Roles endpoint**: 96% faster (50ms → 2ms)
- **Database load**: Reduced by 60-80%
- **Server capacity**: Can handle 5-10x more traffic

**Start with statistics endpoints for maximum impact!** 🚀

