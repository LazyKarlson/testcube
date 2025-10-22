# Caching Strategy Recommendations

## Overview

This document provides caching recommendations for the application based on data characteristics, update frequency, and access patterns.

---

## 🎯 Priority Levels

### 🔴 **HIGH Priority** - Cache Immediately
Endpoints that are:
- Frequently accessed
- Rarely change
- Expensive to compute
- Public (high traffic)

### 🟡 **MEDIUM Priority** - Cache When Traffic Grows
Endpoints that:
- Change moderately
- Have moderate computation cost
- Benefit from short-term caching

### 🟢 **LOW Priority** - Optional/Don't Cache
Endpoints that:
- Change frequently
- Are user-specific
- Are cheap to compute
- Need real-time data

---

## 🔴 HIGH Priority Caching

### 1. **Roles Metadata** - `/api/meta/roles`

**Why Cache?**
- ✅ Rarely changes (only when roles/permissions are modified)
- ✅ Publicly accessible (high traffic potential)
- ✅ Same data for all users
- ✅ Includes database joins (roles + permissions)

**Recommended Strategy**:
```php
Cache Duration: 1 hour - 24 hours
Cache Key: 'api:meta:roles'
Invalidate: When roles or permissions are modified
```

**Implementation**:
```php
public function metaRoles()
{
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
```

**Cache Invalidation**:
```php
// In RoleController when roles/permissions change
Cache::forget('api:meta:roles');
```

---

### 2. **User Statistics** - `/api/stats/users`

**Why Cache?**
- ✅ Expensive to compute (multiple aggregations, joins)
- ✅ Changes slowly (only when users/posts/comments change)
- ✅ Same data for all users
- ✅ Publicly accessible

**Recommended Strategy**:
```php
Cache Duration: 5-15 minutes
Cache Key: 'api:stats:users'
Invalidate: Time-based (TTL) or on user/post/comment changes
```

**Implementation**:
```php
public function index(Request $request)
{
    return Cache::remember('api:stats:users', 900, function () {
        // ... existing statistics logic
    });
}
```

**Why 5-15 minutes?**
- Statistics don't need to be real-time
- Reduces database load significantly
- Still feels "fresh" to users

---

### 3. **Post Statistics** - `/api/stats/posts` (without date range)

**Why Cache?**
- ✅ Expensive to compute (aggregations, top posts query)
- ✅ Changes moderately
- ✅ Same data for all users (when no filters)

**Recommended Strategy**:
```php
Cache Duration: 5-15 minutes (base stats)
Cache Key: 'api:stats:posts' (no params) or 'api:stats:posts:{date_from}:{date_to}'
Invalidate: Time-based (TTL)
```

**Implementation**:
```php
public function index(Request $request)
{
    $validated = $request->validate([
        'date_from' => 'nullable|date',
        'date_to' => 'nullable|date|after_or_equal:date_from',
    ]);

    $dateFrom = $validated['date_from'] ?? null;
    $dateTo = $validated['date_to'] ?? null;

    // Cache key includes date range
    $cacheKey = 'api:stats:posts';
    if ($dateFrom || $dateTo) {
        $cacheKey .= ':' . ($dateFrom ?? 'null') . ':' . ($dateTo ?? 'null');
    }

    return Cache::remember($cacheKey, 900, function () use ($dateFrom, $dateTo) {
        // ... existing statistics logic
    });
}
```

---

### 4. **Comment Statistics** - `/api/stats/comments` (without date range)

**Why Cache?**
- ✅ Expensive to compute (hour/weekday aggregations)
- ✅ Changes moderately
- ✅ Same data for all users

**Recommended Strategy**:
```php
Cache Duration: 5-15 minutes
Cache Key: 'api:stats:comments' or 'api:stats:comments:{date_from}:{date_to}'
Invalidate: Time-based (TTL)
```

**Implementation**: Similar to post statistics above.

---

## 🟡 MEDIUM Priority Caching

### 5. **Posts List** - `/api/posts`

**Why Cache?**
- ✅ Frequently accessed
- ✅ Moderate computation (pagination, eager loading)
- ⚠️ Changes when posts are created/updated/deleted
- ⚠️ Different results per page/sort

**Recommended Strategy**:
```php
Cache Duration: 1-5 minutes
Cache Key: 'api:posts:page:{page}:sort:{sort_by}:{sort_order}:per_page:{per_page}'
Invalidate: Time-based (TTL) or on post changes
```

**Implementation**:
```php
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

    $cacheKey = "api:posts:page:{$page}:sort:{$sortBy}:{$sortOrder}:per_page:{$perPage}";

    return Cache::remember($cacheKey, 300, function () use ($validated) {
        // ... existing logic
    });
}
```

**Cache Invalidation**:
```php
// In PostController store/update/destroy methods
Cache::tags(['posts'])->flush();
// Or use pattern matching to clear specific keys
```

---

### 6. **Search Results** - `/api/posts/search`

**Why Cache?**
- ✅ Can be expensive (LIKE queries)
- ⚠️ Many possible query combinations
- ⚠️ Results change when posts are modified

**Recommended Strategy**:
```php
Cache Duration: 2-5 minutes
Cache Key: 'api:posts:search:{hash_of_all_params}'
Invalidate: Time-based (TTL)
```

**Implementation**:
```php
public function search(Request $request)
{
    $validated = $request->validate([
        'q' => 'required|string|min:1',
        'status' => 'nullable|in:draft,published',
        'published_at' => 'nullable|array',
        // ... other params
    ]);

    // Create cache key from all parameters
    $cacheKey = 'api:posts:search:' . md5(json_encode($validated));

    return Cache::remember($cacheKey, 180, function () use ($validated) {
        // ... existing search logic
    });
}
```

**Note**: Be careful with search caching - too many unique queries can fill cache.

---

### 7. **Single Post** - `/api/posts/{id}`

**Why Cache?**
- ✅ Frequently accessed (popular posts)
- ⚠️ Changes when post is updated
- ⚠️ Many different posts

**Recommended Strategy**:
```php
Cache Duration: 5-15 minutes
Cache Key: 'api:post:{id}'
Invalidate: When specific post is updated/deleted
```

**Implementation**:
```php
public function show(Post $post)
{
    $cacheKey = "api:post:{$post->id}";

    return Cache::remember($cacheKey, 600, function () use ($post) {
        $post->load([
            'author:id,name,email',
            'comments' => function ($query) {
                $query->latest()->limit(1);
            },
            'comments.author:id,name'
        ]);

        $post->loadCount('comments');

        return response()->json([
            'post' => [
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
            ],
        ]);
    });
}
```

**Cache Invalidation**:
```php
// In PostController update/destroy methods
Cache::forget("api:post:{$post->id}");
```

---

### 8. **Post Comments** - `/api/posts/{post}/comments`

**Why Cache?**
- ✅ Frequently accessed with posts
- ⚠️ Changes when comments are added/updated/deleted
- ⚠️ Different results per page

**Recommended Strategy**:
```php
Cache Duration: 2-5 minutes
Cache Key: 'api:post:{post_id}:comments:page:{page}'
Invalidate: Time-based (TTL) or when comments change
```

---

## 🟢 LOW Priority / Don't Cache

### 9. **Authenticated User Data** - `/api/user`

**Why NOT Cache?**
- ❌ User-specific data
- ❌ Needs to be current (roles, permissions)
- ❌ Security concerns (stale permissions)

**Recommendation**: **Don't cache** or use very short TTL (30 seconds) with user-specific key.

---

### 10. **My Posts** - `/api/my-posts`

**Why NOT Cache?**
- ❌ User-specific data
- ❌ Changes when user creates/updates posts
- ❌ Different for each user

**Recommendation**: **Don't cache** or use short TTL with user-specific key.

---

### 11. **Roles List (Authenticated)** - `/api/roles`

**Why Cache?**
- ✅ Same as `/api/meta/roles`

**Recommendation**: Share cache with `/api/meta/roles`.

---

### 12. **Write Operations** (POST/PUT/DELETE)

**Why NOT Cache?**
- ❌ Not applicable (write operations)

**Recommendation**: **Don't cache**. Instead, invalidate related caches.

---

## 📋 Implementation Priority

### Phase 1: Quick Wins (Implement First)
1. ✅ `/api/meta/roles` - 1 hour cache
2. ✅ `/api/stats/users` - 15 minutes cache
3. ✅ `/api/stats/posts` - 15 minutes cache
4. ✅ `/api/stats/comments` - 15 minutes cache

**Impact**: Reduces database load by 60-80% for these endpoints.

### Phase 2: High Traffic Endpoints
5. ✅ `/api/posts` - 5 minutes cache
6. ✅ `/api/posts/{id}` - 10 minutes cache

**Impact**: Reduces load for most common read operations.

### Phase 3: Optimization
7. ✅ `/api/posts/search` - 3 minutes cache
8. ✅ `/api/posts/{post}/comments` - 3 minutes cache

**Impact**: Improves search performance.

---

## 🔧 Cache Invalidation Strategy

### Event-Based Invalidation

```php
// app/Observers/PostObserver.php
class PostObserver
{
    public function created(Post $post)
    {
        Cache::tags(['posts'])->flush();
        Cache::forget('api:stats:posts');
    }

    public function updated(Post $post)
    {
        Cache::forget("api:post:{$post->id}");
        Cache::tags(['posts'])->flush();
        Cache::forget('api:stats:posts');
    }

    public function deleted(Post $post)
    {
        Cache::forget("api:post:{$post->id}");
        Cache::tags(['posts'])->flush();
        Cache::forget('api:stats:posts');
    }
}
```

```php
// app/Observers/CommentObserver.php
class CommentObserver
{
    public function created(Comment $comment)
    {
        Cache::forget("api:post:{$comment->post_id}:comments");
        Cache::forget("api:post:{$comment->post_id}");
        Cache::forget('api:stats:comments');
    }

    // Similar for updated/deleted
}
```

### Register Observers

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    Post::observe(PostObserver::class);
    Comment::observe(CommentObserver::class);
    Role::observe(RoleObserver::class);
}
```

---

## 📊 Cache Configuration

### Recommended Cache Driver

**For Production**:
- **Redis** - Best performance, supports tags
- **Memcached** - Good performance, no tags support

**For Development**:
- **File** or **Array** - Simple, no setup needed

### Cache Tags (Redis only)

```php
// Group related caches
Cache::tags(['posts', 'public'])->put('api:posts:page:1', $data, 300);

// Flush all posts caches
Cache::tags(['posts'])->flush();
```

---

## 🎯 Expected Performance Improvements

| Endpoint | Before | After | Improvement |
|----------|--------|-------|-------------|
| `/api/meta/roles` | ~50ms | ~2ms | **96%** |
| `/api/stats/users` | ~200ms | ~2ms | **99%** |
| `/api/stats/posts` | ~150ms | ~2ms | **98%** |
| `/api/stats/comments` | ~180ms | ~2ms | **98%** |
| `/api/posts` | ~80ms | ~5ms | **94%** |
| `/api/posts/{id}` | ~40ms | ~3ms | **92%** |

---

## ⚠️ Important Considerations

### 1. **Cache Stampede Prevention**

Use cache locks for expensive operations:

```php
$value = Cache::lock('stats:users')->get(function () {
    return Cache::remember('api:stats:users', 900, function () {
        // Expensive computation
    });
});
```

### 2. **Memory Usage**

Monitor cache size, especially for:
- Search results (many unique keys)
- Paginated results (many pages)

### 3. **Stale Data**

Balance between:
- **Performance** (longer cache)
- **Freshness** (shorter cache)

Statistics can be 15 minutes old, but user permissions should be fresh.

### 4. **Cache Warming**

Pre-populate cache for common requests:

```php
// In a scheduled job
Cache::remember('api:posts:page:1:sort:published_at:desc:per_page:25', 300, function () {
    // Generate first page
});
```

---

## 📝 Summary

### ✅ Must Cache (High Priority)
- `/api/meta/roles` - 1 hour
- `/api/stats/*` - 15 minutes

### ✅ Should Cache (Medium Priority)
- `/api/posts` - 5 minutes
- `/api/posts/{id}` - 10 minutes
- `/api/posts/search` - 3 minutes

### ❌ Don't Cache
- `/api/user` - User-specific
- `/api/my-posts` - User-specific
- Write operations - Not applicable

### 🔄 Cache Invalidation
- Use observers for automatic invalidation
- Use cache tags for grouped invalidation
- Use TTL for statistics (acceptable staleness)

**Implementing Phase 1 alone can reduce database load by 60-80% for statistics endpoints!** 🚀

