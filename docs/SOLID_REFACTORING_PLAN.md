# SOLID Refactoring Plan

## 🎯 Step-by-Step Implementation Guide

This document provides a **concrete, actionable plan** for refactoring your Laravel Blog API to comply with SOLID principles.

---

## Phase 1: Foundation (Week 1) - Quick Wins

### Step 1.1: Create Transformer Classes (2 hours)

**Goal:** Eliminate duplicate transformation logic

#### Create `app/Transformers/PostTransformer.php`

```php
<?php

namespace App\Transformers;

use App\Models\Post;
use Illuminate\Support\Collection;

class PostTransformer
{
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
            'comments_count' => $post->comments_count,
            'last_comment' => $lastComment ? $this->transformLastComment($lastComment) : null,
        ];
    }

    public function transformCollection(Collection $posts): Collection
    {
        return $posts->map(fn($post) => $this->transform($post));
    }

    private function transformAuthor($author): array
    {
        return [
            'name' => $author->name,
            'email' => $author->email,
        ];
    }

    private function transformLastComment($comment): array
    {
        return [
            'body' => $comment->body,
            'author_name' => $comment->author->name,
        ];
    }
}
```

#### Update `PostController.php`

```php
class PostController extends Controller
{
    public function __construct(
        private PostTransformer $transformer
    ) {}

    public function index(Request $request)
    {
        // ... validation and caching ...
        
        $posts = Post::with([...])->paginate($perPage);
        
        // Replace inline transformation with:
        $posts->setCollection(
            $this->transformer->transformCollection($posts->getCollection())
        );
        
        return response()->json($posts);
    }
}
```

**Benefits:**
- ✅ Eliminates 40+ lines of duplicate code
- ✅ Single source of truth for transformations
- ✅ Easy to test
- ✅ Easy to extend

---

### Step 1.2: Create Authorization Policies (1 hour)

**Goal:** Move authorization logic out of controllers

#### Create `app/Policies/PostPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine if the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        return $post->author_id === $user->id || $user->isAdmin();
    }

    /**
     * Determine if the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        return $post->author_id === $user->id || $user->isAdmin();
    }

    /**
     * Determine if the user can view the post.
     */
    public function view(?User $user, Post $post): bool
    {
        // Public posts can be viewed by anyone
        if ($post->isPublished()) {
            return true;
        }

        // Draft posts can only be viewed by author or admin
        return $user && ($post->author_id === $user->id || $user->isAdmin());
    }
}
```

#### Register in `app/Providers/AppServiceProvider.php`

```php
use App\Models\Post;
use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::policy(Post::class, PostPolicy::class);
}
```

#### Update `PostController.php`

```php
public function update(Request $request, Post $post)
{
    // Replace lines 132-136 with:
    $this->authorize('update', $post);
    
    $validated = $request->validate([...]);
    $post->update($validated);
    
    return response()->json([...]);
}

public function destroy(Request $request, Post $post)
{
    // Replace lines 173-177 with:
    $this->authorize('delete', $post);
    
    $post->delete();
    
    return response()->json([...]);
}
```

**Benefits:**
- ✅ Removes 10+ lines from controller
- ✅ Centralized authorization logic
- ✅ Reusable across application
- ✅ Easier to test

---

### Step 1.3: Create CommentPolicy (30 minutes)

Same pattern as PostPolicy for comments.

---

## Phase 2: Service Layer (Week 2-3)

### Step 2.1: Create Post Service (4 hours)

**Goal:** Extract business logic from controller

#### Create `app/Services/PostService.php`

```php
<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class PostService
{
    /**
     * Create a new post.
     */
    public function create(User $author, array $data): Post
    {
        // Business logic for creating posts
        $data = $this->preparePostData($data);
        
        return $author->posts()->create($data);
    }

    /**
     * Update an existing post.
     */
    public function update(Post $post, array $data): Post
    {
        $data = $this->preparePostData($data, $post);
        
        $post->update($data);
        
        return $post->fresh();
    }

    /**
     * Delete a post.
     */
    public function delete(Post $post): bool
    {
        return $post->delete();
    }

    /**
     * Get paginated posts with relationships.
     */
    public function getPaginated(int $perPage, string $sortBy, string $sortOrder): LengthAwarePaginator
    {
        return Post::with([
            'author:id,name,email',
            'comments' => fn($q) => $q->latest()->limit(1)->with('author:id,name'),
        ])
            ->withCount('comments')
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }

    /**
     * Prepare post data with business rules.
     */
    private function preparePostData(array $data, ?Post $post = null): array
    {
        // Auto-set published_at if status is published
        if (isset($data['status']) && $data['status'] === 'published') {
            if (!isset($data['published_at']) && (!$post || !$post->isPublished())) {
                $data['published_at'] = now();
            }
        }

        // Clear published_at when changing to draft
        if (isset($data['status']) && $data['status'] === 'draft') {
            $data['published_at'] = null;
        }

        // Default status to draft
        if (!isset($data['status'])) {
            $data['status'] = 'draft';
        }

        return $data;
    }
}
```

#### Update `PostController.php`

```php
class PostController extends Controller
{
    public function __construct(
        private PostService $postService,
        private PostTransformer $transformer
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([...]);
        
        // Replace lines 92-101 with:
        $post = $this->postService->create($request->user(), $validated);
        
        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post->load('author:id,name,email'),
        ], 201);
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);
        
        $validated = $request->validate([...]);
        
        // Replace lines 145-157 with:
        $post = $this->postService->update($post, $validated);
        
        return response()->json([...]);
    }
}
```

**Benefits:**
- ✅ Business logic separated from HTTP layer
- ✅ Reusable across application (API, CLI, Jobs)
- ✅ Easy to test in isolation
- ✅ Single Responsibility Principle

---

### Step 2.2: Create Statistics Services (6 hours)

#### Create `app/Services/Statistics/PostStatisticsService.php`

```php
<?php

namespace App\Services\Statistics;

use App\Models\Post;
use Illuminate\Support\Facades\DB;

class PostStatisticsService
{
    public function getStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        return [
            'posts_by_status' => $this->getPostsByStatus(),
            'posts_by_date_range' => $this->getPostsByDateRange($dateFrom, $dateTo),
            'average_comments_per_post' => $this->getAverageCommentsPerPost(),
            'top_commented_posts' => $this->getTopCommentedPosts(),
            'total_posts' => Post::count(),
        ];
    }

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

    private function getPostsByDateRange(?string $dateFrom, ?string $dateTo): ?array
    {
        if (!$dateFrom && !$dateTo) {
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

    private function getAverageCommentsPerPost(): float
    {
        return round(
            Post::withCount('comments')->get()->avg('comments_count'),
            2
        );
    }

    private function getTopCommentedPosts(): array
    {
        return Post::withCount('comments')
            ->with('author:id,name,email')
            ->orderBy('comments_count', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($post) => [
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
}
```

#### Create similar services:
- `CommentStatisticsService`
- `UserStatisticsService`

#### Update `StatsController.php`

```php
class StatsController extends Controller
{
    public function __construct(
        private PostStatisticsService $postStats,
        private CommentStatisticsService $commentStats,
        private UserStatisticsService $userStats
    ) {}

    public function posts(Request $request)
    {
        $validated = $request->validate([...]);
        
        $cacheKey = 'api:stats:posts';
        if ($validated['date_from'] ?? null) {
            $cacheKey .= ':'.$validated['date_from'].':'.$validated['date_to'];
        }
        
        return Cache::remember($cacheKey, 900, function () use ($validated) {
            $stats = $this->postStats->getStatistics(
                $validated['date_from'] ?? null,
                $validated['date_to'] ?? null
            );
            
            return response()->json($stats);
        });
    }
}
```

**Benefits:**
- ✅ StatsController reduced from 347 to ~100 lines
- ✅ Each service has single responsibility
- ✅ Easy to test statistics logic
- ✅ Reusable in other contexts

---

## Phase 3: Repository Pattern (Week 4-5)

### Step 3.1: Create Repository Interfaces (2 hours)

#### Create `app/Contracts/PostRepositoryInterface.php`

```php
<?php

namespace App\Contracts;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostRepositoryInterface
{
    public function find(int $id): ?Post;
    
    public function getPaginated(int $perPage, string $sortBy, string $sortOrder): LengthAwarePaginator;
    
    public function search(array $criteria): LengthAwarePaginator;
    
    public function create(array $data): Post;
    
    public function update(Post $post, array $data): Post;
    
    public function delete(Post $post): bool;
    
    public function getByAuthor(int $authorId, int $perPage): LengthAwarePaginator;
}
```

---

### Step 3.2: Implement Repositories (4 hours)

#### Create `app/Repositories/PostRepository.php`

```php
<?php

namespace App\Repositories;

use App\Contracts\PostRepositoryInterface;
use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

class PostRepository implements PostRepositoryInterface
{
    public function find(int $id): ?Post
    {
        return Post::with(['author:id,name,email', 'comments.author:id,name,email'])
            ->find($id);
    }

    public function getPaginated(int $perPage, string $sortBy, string $sortOrder): LengthAwarePaginator
    {
        return Post::with([
            'author:id,name,email',
            'comments' => fn($q) => $q->latest()->limit(1)->with('author:id,name'),
        ])
            ->withCount('comments')
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }

    public function search(array $criteria): LengthAwarePaginator
    {
        $query = Post::query();

        if (isset($criteria['q'])) {
            $query->where(function ($q) use ($criteria) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($criteria['q']).'%'])
                  ->orWhereRaw('LOWER(body) LIKE ?', ['%'.strtolower($criteria['q']).'%']);
            });
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (isset($criteria['published_from'])) {
            $query->whereDate('published_at', '>=', $criteria['published_from']);
        }

        if (isset($criteria['published_to'])) {
            $query->whereDate('published_at', '<=', $criteria['published_to']);
        }

        return $query->with([
            'author:id,name,email',
            'comments' => fn($q) => $q->latest()->limit(1)->with('author:id,name'),
        ])
            ->withCount('comments')
            ->orderBy($criteria['sort_by'] ?? 'published_at', $criteria['sort_order'] ?? 'desc')
            ->paginate($criteria['per_page'] ?? 25);
    }

    public function create(array $data): Post
    {
        return Post::create($data);
    }

    public function update(Post $post, array $data): Post
    {
        $post->update($data);
        return $post->fresh();
    }

    public function delete(Post $post): bool
    {
        return $post->delete();
    }

    public function getByAuthor(int $authorId, int $perPage): LengthAwarePaginator
    {
        return Post::where('author_id', $authorId)
            ->latest()
            ->paginate($perPage);
    }
}
```

---

### Step 3.3: Bind Interfaces to Implementations (15 minutes)

#### Update `app/Providers/AppServiceProvider.php`

```php
public function register(): void
{
    $this->app->bind(
        \App\Contracts\PostRepositoryInterface::class,
        \App\Repositories\PostRepository::class
    );
    
    $this->app->bind(
        \App\Contracts\CommentRepositoryInterface::class,
        \App\Repositories\CommentRepository::class
    );
    
    // ... more bindings
}
```

---

## 📊 Progress Tracking

| Phase | Tasks | Estimated Time | Status |
|-------|-------|----------------|--------|
| Phase 1 | Transformers + Policies | 4 hours | ⏳ Not Started |
| Phase 2 | Service Layer | 16 hours | ⏳ Not Started |
| Phase 3 | Repository Pattern | 20 hours | ⏳ Not Started |
| Phase 4 | Cache Service | 8 hours | ⏳ Not Started |
| Phase 5 | Event-Driven Architecture | 12 hours | ⏳ Not Started |
| **Total** | **All Phases** | **60 hours** | **0% Complete** |

---

## 🎯 Success Metrics

After refactoring, you should achieve:

- ✅ **Controllers**: < 100 lines each
- ✅ **Methods**: < 20 lines each
- ✅ **Test Coverage**: > 80%
- ✅ **Cyclomatic Complexity**: < 10
- ✅ **Code Duplication**: < 3%

---

**Next:** See `SOLID_EXAMPLES.md` for complete code examples.


