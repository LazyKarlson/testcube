# SOLID Principles - Quick Reference

## 📚 Overview

Quick reference guide for applying SOLID principles to your Laravel Blog API.

---

## 🎯 The SOLID Principles

### **S** - Single Responsibility Principle
> A class should have one, and only one, reason to change.

**❌ Bad:**
```php
class PostController {
    public function index() {
        // Validation
        // Query building
        // Caching
        // Transformation
        // Response formatting
    }
}
```

**✅ Good:**
```php
class PostController {
    public function __construct(
        private PostService $service,
        private PostTransformer $transformer,
        private CacheService $cache
    ) {}
    
    public function index(Request $request) {
        $posts = $this->service->getPaginated($request->validated());
        return response()->json($this->transformer->transformCollection($posts));
    }
}
```

---

### **O** - Open/Closed Principle
> Software entities should be open for extension, but closed for modification.

**❌ Bad:**
```php
public function store(Request $request) {
    if ($validated['status'] === 'published') {
        $validated['published_at'] = now();
    } elseif ($validated['status'] === 'draft') {
        $validated['published_at'] = null;
    }
    // Adding 'archived' status requires modifying this code
}
```

**✅ Good:**
```php
interface PostStatusHandler {
    public function handle(Post $post): void;
}

class PublishedStatusHandler implements PostStatusHandler {
    public function handle(Post $post): void {
        $post->published_at = now();
    }
}

class PostService {
    public function __construct(
        private array $statusHandlers = []
    ) {}
    
    public function setStatus(Post $post, string $status): void {
        $this->statusHandlers[$status]->handle($post);
    }
}
```

---

### **L** - Liskov Substitution Principle
> Objects should be replaceable with instances of their subtypes.

**❌ Bad:**
```php
class PostRepository {
    public function find(int $id): Post {
        return Post::findOrFail($id);
    }
}

class CachedPostRepository extends PostRepository {
    public function find(int $id): ?Post {  // ❌ Changed return type
        return Cache::remember("post:$id", 600, fn() => parent::find($id));
    }
}
```

**✅ Good:**
```php
interface PostRepositoryInterface {
    public function find(int $id): ?Post;
}

class PostRepository implements PostRepositoryInterface {
    public function find(int $id): ?Post {
        return Post::find($id);
    }
}

class CachedPostRepository implements PostRepositoryInterface {
    public function __construct(private PostRepositoryInterface $repository) {}
    
    public function find(int $id): ?Post {
        return Cache::remember("post:$id", 600, fn() => $this->repository->find($id));
    }
}
```

---

### **I** - Interface Segregation Principle
> Clients should not be forced to depend on interfaces they don't use.

**❌ Bad:**
```php
interface PostRepositoryInterface {
    public function find(int $id): ?Post;
    public function create(array $data): Post;
    public function update(Post $post, array $data): Post;
    public function delete(Post $post): bool;
    public function search(array $criteria): Collection;
    public function getStatistics(): array;  // ❌ Not all clients need this
    public function export(): string;        // ❌ Not all clients need this
}
```

**✅ Good:**
```php
interface PostRepositoryInterface {
    public function find(int $id): ?Post;
    public function create(array $data): Post;
    public function update(Post $post, array $data): Post;
    public function delete(Post $post): bool;
}

interface PostSearchInterface {
    public function search(array $criteria): Collection;
}

interface PostStatisticsInterface {
    public function getStatistics(): array;
}

interface PostExportInterface {
    public function export(): string;
}
```

---

### **D** - Dependency Inversion Principle
> Depend on abstractions, not concretions.

**❌ Bad:**
```php
class PostController {
    public function index() {
        // Direct dependency on Cache facade
        return Cache::remember('posts', 300, function() {
            // Direct dependency on Post model
            return Post::with('author')->paginate(25);
        });
    }
}
```

**✅ Good:**
```php
class PostController {
    public function __construct(
        private PostRepositoryInterface $repository,
        private CacheServiceInterface $cache
    ) {}
    
    public function index() {
        return $this->cache->remember('posts', 300, function() {
            return $this->repository->getPaginated(25);
        });
    }
}
```

---

## 🏗️ Recommended Architecture

```
app/
├── Http/
│   ├── Controllers/        # Thin controllers (HTTP layer only)
│   ├── Requests/          # Form requests (validation)
│   ├── Resources/         # API resources (transformation)
│   └── Middleware/        # HTTP middleware
├── Services/              # Business logic
│   ├── PostService.php
│   ├── AuthService.php
│   └── Statistics/
│       ├── PostStatisticsService.php
│       └── UserStatisticsService.php
├── Repositories/          # Data access layer
│   ├── PostRepository.php
│   └── UserRepository.php
├── Contracts/             # Interfaces
│   ├── PostRepositoryInterface.php
│   └── CacheServiceInterface.php
├── Transformers/          # Data transformation
│   ├── PostTransformer.php
│   └── UserTransformer.php
├── Policies/              # Authorization
│   ├── PostPolicy.php
│   └── CommentPolicy.php
├── Events/                # Domain events
│   ├── PostCreated.php
│   └── PostPublished.php
├── Listeners/             # Event handlers
│   ├── ClearPostCache.php
│   └── SendNotification.php
└── Models/                # Eloquent models (data only)
    ├── Post.php
    └── User.php
```

---

## 📋 Refactoring Checklist

### Controllers
- [ ] Controllers < 100 lines
- [ ] Methods < 20 lines
- [ ] No business logic
- [ ] No direct model queries
- [ ] Use dependency injection
- [ ] Use policies for authorization
- [ ] Use form requests for validation
- [ ] Use resources for transformation

### Services
- [ ] Single responsibility
- [ ] No HTTP concerns
- [ ] Testable in isolation
- [ ] Depend on interfaces
- [ ] Return domain objects

### Repositories
- [ ] Implement interfaces
- [ ] Only data access logic
- [ ] No business logic
- [ ] Return models/collections
- [ ] Consistent method naming

### Models
- [ ] Only relationships
- [ ] Only accessors/mutators
- [ ] Only scopes
- [ ] No business logic
- [ ] No HTTP concerns

---

## 🎯 Common Patterns

### Repository Pattern

```php
// Interface
interface PostRepositoryInterface {
    public function find(int $id): ?Post;
    public function getPaginated(int $perPage): LengthAwarePaginator;
}

// Implementation
class PostRepository implements PostRepositoryInterface {
    public function find(int $id): ?Post {
        return Post::with('author')->find($id);
    }
    
    public function getPaginated(int $perPage): LengthAwarePaginator {
        return Post::with('author')->paginate($perPage);
    }
}

// Binding (AppServiceProvider)
$this->app->bind(PostRepositoryInterface::class, PostRepository::class);

// Usage (Controller)
public function __construct(private PostRepositoryInterface $posts) {}
```

---

### Service Pattern

```php
class PostService {
    public function __construct(
        private PostRepositoryInterface $repository
    ) {}
    
    public function create(User $author, array $data): Post {
        // Business logic
        $data = $this->prepareData($data);
        
        // Delegate to repository
        return $this->repository->create($data);
    }
    
    private function prepareData(array $data): array {
        // Business rules
        if ($data['status'] === 'published' && !isset($data['published_at'])) {
            $data['published_at'] = now();
        }
        return $data;
    }
}
```

---

### Transformer Pattern

```php
class PostTransformer {
    public function transform(Post $post): array {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'author' => [
                'name' => $post->author->name,
                'email' => $post->author->email,
            ],
        ];
    }
    
    public function transformCollection(Collection $posts): Collection {
        return $posts->map(fn($post) => $this->transform($post));
    }
}
```

---

### Policy Pattern

```php
class PostPolicy {
    public function update(User $user, Post $post): bool {
        return $post->author_id === $user->id || $user->isAdmin();
    }
}

// Controller
public function update(Request $request, Post $post) {
    $this->authorize('update', $post);
    // ...
}
```

---

### Event-Driven Pattern

```php
// Event
class PostCreated {
    public function __construct(public Post $post) {}
}

// Listener
class ClearPostCache {
    public function __construct(private CacheServiceInterface $cache) {}
    
    public function handle(PostCreated $event): void {
        $this->cache->forget('posts');
    }
}

// Service
class PostService {
    public function create(array $data): Post {
        $post = $this->repository->create($data);
        event(new PostCreated($post));
        return $post;
    }
}
```

---

## 🚀 Quick Start

### 1. Start with Transformers (Easiest)

```bash
php artisan make:class Transformers/PostTransformer
```

### 2. Add Policies (Quick Win)

```bash
php artisan make:policy PostPolicy --model=Post
```

### 3. Create Services (Medium)

```bash
php artisan make:class Services/PostService
```

### 4. Implement Repositories (Advanced)

```bash
php artisan make:class Contracts/PostRepositoryInterface
php artisan make:class Repositories/PostRepository
```

---

## 📊 Benefits Summary

| Principle | Benefit | Impact |
|-----------|---------|--------|
| **SRP** | Easier to understand and maintain | High |
| **OCP** | Add features without breaking existing code | High |
| **LSP** | Reliable inheritance and polymorphism | Medium |
| **ISP** | Smaller, focused interfaces | Medium |
| **DIP** | Testable, flexible, decoupled code | High |

---

## 🎓 Learning Resources

- **SOLID Refactoring Guide** → `SOLID_REFACTORING_GUIDE.md`
- **Step-by-Step Plan** → `SOLID_REFACTORING_PLAN.md`
- **Code Examples** → `SOLID_EXAMPLES.md` (coming soon)
- **Migration Guide** → `SOLID_MIGRATION_GUIDE.md` (coming soon)

---

## ⚠️ Common Mistakes

### ❌ Don't:
- Put business logic in controllers
- Query models directly in controllers
- Use facades directly (use DI instead)
- Create "God" classes with too many responsibilities
- Violate interface contracts in implementations
- Create circular dependencies

### ✅ Do:
- Keep controllers thin (< 100 lines)
- Use dependency injection
- Program to interfaces, not implementations
- Follow single responsibility principle
- Write tests for all business logic
- Use Laravel's built-in features (Policies, Events, etc.)

---

**Ready to start?** → See `SOLID_REFACTORING_PLAN.md` for step-by-step instructions!


