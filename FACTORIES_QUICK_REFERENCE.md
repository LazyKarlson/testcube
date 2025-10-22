# Factories Quick Reference

## 🚀 Quick Start

```bash
# In tinker or seeders
php artisan tinker
```

## 📝 PostFactory

### Basic
```php
Post::factory()->create();                    // Single post
Post::factory()->count(10)->create();         // 10 posts
```

### States
```php
Post::factory()->published()->create();       // Published post
Post::factory()->draft()->create();           // Draft post
Post::factory()->recent()->create();          // Published in last 7 days
Post::factory()->forAuthor($user)->create();  // For specific user
```

### Combined
```php
// 5 recent published posts by specific user
Post::factory()
    ->count(5)
    ->published()
    ->recent()
    ->forAuthor($user)
    ->create();
```

### With Relationships
```php
// Post with 5 comments
Post::factory()
    ->has(Comment::factory()->count(5))
    ->create();

// Post with comments from specific user
Post::factory()
    ->has(Comment::factory()->count(3)->forAuthor($user))
    ->create();
```

## 💬 CommentFactory

### Basic
```php
Comment::factory()->create();                 // Single comment
Comment::factory()->count(10)->create();      // 10 comments
```

### States
```php
Comment::factory()->short()->create();        // Short comment (1 sentence)
Comment::factory()->long()->create();         // Long comment (5 paragraphs)
Comment::factory()->forAuthor($user)->create(); // By specific user
Comment::factory()->forPost($post)->create(); // On specific post
```

### Combined
```php
// 10 short comments by user on specific post
Comment::factory()
    ->count(10)
    ->short()
    ->forAuthor($user)
    ->forPost($post)
    ->create();
```

## 👤 UserFactory

### Basic
```php
User::factory()->create();                    // Single user
User::factory()->count(10)->create();         // 10 users
```

### States
```php
User::factory()->unverified()->create();      // Unverified email
```

### With Relationships
```php
// User with 5 posts
User::factory()
    ->has(Post::factory()->count(5))
    ->create();

// User with posts and comments
User::factory()
    ->has(Post::factory()->count(5))
    ->has(Comment::factory()->count(10))
    ->create();
```

## 🎯 Common Patterns

### Blog with Authors and Content
```php
// Create 5 authors, each with 3 posts, each post with 5 comments
User::factory()
    ->count(5)
    ->has(
        Post::factory()
            ->count(3)
            ->has(Comment::factory()->count(5))
    )
    ->create();
```

### Specific Scenario
```php
// Create a popular post
$popularPost = Post::factory()
    ->published()
    ->has(Comment::factory()->count(50))
    ->create(['title' => 'Viral Post']);
```

### Test Data for User
```php
$user = User::factory()->create();

// User's published posts
Post::factory()->count(10)->published()->forAuthor($user)->create();

// User's draft posts
Post::factory()->count(5)->draft()->forAuthor($user)->create();

// User's comments on other posts
Comment::factory()->count(20)->forAuthor($user)->create();
```

## 🧪 Testing Examples

```php
// In tests
public function test_example(): void
{
    $user = User::factory()->create();
    $post = Post::factory()->forAuthor($user)->create();
    
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'author_id' => $user->id,
    ]);
}
```

## 🌱 Seeding

```bash
# Run specific seeder
php artisan db:seed --class=BlogSeeder

# Run all seeders
php artisan db:seed

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

## 📊 Field Defaults

### PostFactory
- `author_id`: Random user
- `title`: Unique 6-word sentence
- `body`: 3 paragraphs
- `status`: Random (draft/published)
- `published_at`: Date if published, null if draft

### CommentFactory
- `author_id`: Random user
- `post_id`: Random post
- `body`: 1 paragraph

### UserFactory
- `name`: Random name
- `email`: Unique email
- `email_verified_at`: Now
- `password`: 'password' (hashed)

## 🔧 Customization

### Override Defaults
```php
Post::factory()->create([
    'title' => 'Custom Title',
    'body' => 'Custom body text',
    'status' => 'published',
]);
```

### Make Without Saving
```php
$post = Post::factory()->make();  // Not saved to DB
$post->title = 'Modified';
$post->save();
```

### Create Multiple with Different Data
```php
Post::factory()->count(3)->create()->each(function ($post, $index) {
    $post->update(['title' => "Post #{$index}"]);
});
```

## 📚 Resources

- Full Guide: `FACTORIES_GUIDE.md`
- Laravel Docs: https://laravel.com/docs/eloquent-factories

---

**Quick Tip**: Use `php artisan tinker` to test factories interactively!

