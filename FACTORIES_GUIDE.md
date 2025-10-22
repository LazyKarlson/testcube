# Model Factories Guide

## Overview

Model factories have been created for Posts and Comments to make testing and seeding easier. These factories use Laravel's built-in factory system with Faker to generate realistic test data.

## Available Factories

### 1. PostFactory
### 2. CommentFactory
### 3. UserFactory (already existed)

---

## PostFactory

**Location**: `src/database/factories/PostFactory.php`

### Default State

Creates a post with random data:
- `author_id`: Random user (creates one if needed)
- `title`: Unique sentence (6 words)
- `body`: 3 paragraphs of text
- `status`: Random ('draft' or 'published')
- `published_at`: Date if published, null if draft

### Basic Usage

```php
use App\Models\Post;

// Create a single post
$post = Post::factory()->create();

// Create multiple posts
$posts = Post::factory()->count(10)->create();

// Create without saving to database
$post = Post::factory()->make();
```

### State Methods

#### `published()`
Creates a published post with a publication date.

```php
$post = Post::factory()->published()->create();
// status: 'published'
// published_at: random date within last year
```

#### `draft()`
Creates a draft post.

```php
$post = Post::factory()->draft()->create();
// status: 'draft'
// published_at: null
```

#### `recent()`
Creates a recently published post (within last 7 days).

```php
$post = Post::factory()->recent()->create();
// status: 'published'
// published_at: random date within last 7 days
```

#### `forAuthor(User $user)`
Creates a post for a specific author.

```php
$user = User::factory()->create();
$post = Post::factory()->forAuthor($user)->create();
// author_id: $user->id
```

### Advanced Examples

```php
// Create 5 published posts
Post::factory()->count(5)->published()->create();

// Create 3 draft posts for a specific user
$user = User::factory()->create();
Post::factory()->count(3)->draft()->forAuthor($user)->create();

// Create a recent post with custom title
Post::factory()->recent()->create([
    'title' => 'My Custom Title',
]);

// Create posts with relationships
$user = User::factory()->create();
$posts = Post::factory()
    ->count(5)
    ->forAuthor($user)
    ->has(Comment::factory()->count(3), 'comments')
    ->create();
```

---

## CommentFactory

**Location**: `src/database/factories/CommentFactory.php`

### Default State

Creates a comment with random data:
- `author_id`: Random user (creates one if needed)
- `post_id`: Random post (creates one if needed)
- `body`: Single paragraph of text

### Basic Usage

```php
use App\Models\Comment;

// Create a single comment
$comment = Comment::factory()->create();

// Create multiple comments
$comments = Comment::factory()->count(10)->create();

// Create without saving to database
$comment = Comment::factory()->make();
```

### State Methods

#### `forAuthor(User $user)`
Creates a comment for a specific author.

```php
$user = User::factory()->create();
$comment = Comment::factory()->forAuthor($user)->create();
// author_id: $user->id
```

#### `forPost(Post $post)`
Creates a comment for a specific post.

```php
$post = Post::factory()->create();
$comment = Comment::factory()->forPost($post)->create();
// post_id: $post->id
```

#### `short()`
Creates a short comment (single sentence).

```php
$comment = Comment::factory()->short()->create();
// body: single sentence
```

#### `long()`
Creates a long comment (5 paragraphs).

```php
$comment = Comment::factory()->long()->create();
// body: 5 paragraphs
```

### Advanced Examples

```php
// Create 10 comments for a specific post
$post = Post::factory()->create();
Comment::factory()->count(10)->forPost($post)->create();

// Create comments from different users on a post
$post = Post::factory()->create();
$users = User::factory()->count(5)->create();

foreach ($users as $user) {
    Comment::factory()
        ->forAuthor($user)
        ->forPost($post)
        ->create();
}

// Create a post with 5 short comments
$post = Post::factory()
    ->has(Comment::factory()->count(5)->short(), 'comments')
    ->create();

// Create long comments for a specific user
$user = User::factory()->create();
Comment::factory()
    ->count(3)
    ->long()
    ->forAuthor($user)
    ->create();
```

---

## Combined Usage Examples

### Create a Complete Blog Structure

```php
// Create 3 authors
$authors = User::factory()->count(3)->create();

// Each author creates 5 posts
foreach ($authors as $author) {
    Post::factory()
        ->count(5)
        ->forAuthor($author)
        ->create();
}

// Add comments to all posts
$posts = Post::all();
foreach ($posts as $post) {
    // Random number of comments (1-10) per post
    Comment::factory()
        ->count(rand(1, 10))
        ->forPost($post)
        ->create();
}
```

### Create Posts with Specific Scenarios

```php
// Scenario 1: Popular published post with many comments
$popularPost = Post::factory()
    ->published()
    ->has(Comment::factory()->count(20), 'comments')
    ->create(['title' => 'Very Popular Post']);

// Scenario 2: Draft post with no comments
$draftPost = Post::factory()
    ->draft()
    ->create(['title' => 'Work in Progress']);

// Scenario 3: Recent post with a few comments
$recentPost = Post::factory()
    ->recent()
    ->has(Comment::factory()->count(3)->short(), 'comments')
    ->create();
```

### Create Test Data for Specific User

```php
$user = User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Create 10 published posts
Post::factory()
    ->count(10)
    ->published()
    ->forAuthor($user)
    ->create();

// Create 5 draft posts
Post::factory()
    ->count(5)
    ->draft()
    ->forAuthor($user)
    ->create();

// Add comments to other posts
$otherPosts = Post::where('author_id', '!=', $user->id)->get();
foreach ($otherPosts as $post) {
    Comment::factory()
        ->forAuthor($user)
        ->forPost($post)
        ->create();
}
```

---

## Using in Seeders

### Example Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 users
        $users = User::factory()->count(10)->create();

        // Each user creates 3-7 posts
        $users->each(function ($user) {
            Post::factory()
                ->count(rand(3, 7))
                ->forAuthor($user)
                ->create()
                ->each(function ($post) use ($user) {
                    // Add 2-5 comments to each post
                    Comment::factory()
                        ->count(rand(2, 5))
                        ->forPost($post)
                        ->create();
                });
        });
    }
}
```

---

## Testing Examples

### Feature Test Example

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_post(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->postJson('/api/posts', [
            'title' => 'Test Post',
            'body' => 'Test content',
            'status' => 'published',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'author_id' => $user->id,
        ]);
    }

    public function test_user_can_only_edit_own_posts(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $post = Post::factory()->forAuthor($user1)->create();

        $response = $this->actingAs($user2)->putJson("/api/posts/{$post->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(403);
    }

    public function test_post_with_comments_can_be_deleted(): void
    {
        $post = Post::factory()
            ->has(Comment::factory()->count(5), 'comments')
            ->create();

        $this->assertDatabaseCount('comments', 5);

        $post->delete();

        $this->assertDatabaseCount('comments', 0);
    }
}
```

---

## Tips and Best Practices

### 1. Use Factories in Tests
Always use factories instead of manually creating models in tests:

```php
// ❌ Bad
$user = User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
]);

// ✅ Good
$user = User::factory()->create();
```

### 2. Chain State Methods
Combine multiple state methods for complex scenarios:

```php
$post = Post::factory()
    ->published()
    ->recent()
    ->forAuthor($user)
    ->create();
```

### 3. Override Specific Attributes
Pass an array to `create()` or `make()` to override defaults:

```php
$post = Post::factory()->create([
    'title' => 'Specific Title',
    'status' => 'draft',
]);
```

### 4. Use Relationships
Leverage Laravel's factory relationships:

```php
// Create post with comments
$post = Post::factory()
    ->has(Comment::factory()->count(3))
    ->create();

// Create comment with post and author
$comment = Comment::factory()
    ->for(Post::factory()->published())
    ->for(User::factory(), 'author')
    ->create();
```

---

## Files Created

1. ✅ `src/database/factories/PostFactory.php`
2. ✅ `src/database/factories/CommentFactory.php`
3. ✅ `FACTORIES_GUIDE.md` (this file)

## Files Modified

1. ✅ `src/app/Models/Post.php` - Added `HasFactory` trait
2. ✅ `src/app/Models/Comment.php` - Added `HasFactory` trait

---

**All factories are ready to use!** Start generating test data with ease. 🎉

