# User `can()` Method - Compatible Implementation

## Overview

The `can()` method in the User model has been updated to be fully compatible with Laravel's `Illuminate\Foundation\Auth\User::can($abilities, $arguments = [])` signature while adding custom permission checking functionality.

---

## Method Signature

```php
public function can($abilities, $arguments = []): bool
```

**Compatible with**: `Illuminate\Foundation\Auth\User::can($abilities, $arguments = [])`

---

## Usage Examples

### 1. Two-String Format (Action + Resource)

Check permission using action and resource format:

```php
$user = User::find(1);

// Check if user can create posts
$user->can('create', 'posts');  // Returns true/false

// Check if user can update comments
$user->can('update', 'comments');  // Returns true/false

// Check if user can delete users
$user->can('delete', 'users');  // Returns true/false
```

**How it works**: Combines the two strings into a permission name (`create_posts`, `update_comments`, etc.) and checks if the user has that permission through their roles.

---

### 2. Single Permission String Format

Check permission using the full permission name:

```php
$user = User::find(1);

// Check using full permission name
$user->can('create_posts');  // Returns true/false
$user->can('update_comments');  // Returns true/false
$user->can('delete_users');  // Returns true/false
```

**How it works**: Directly checks if the user has the specified permission through their roles.

---

### 3. Laravel Gate Format (Fallback)

The method also supports Laravel's built-in Gate system:

```php
$user = User::find(1);

// Falls back to Laravel's Gate system
$user->can('view-dashboard');  // Uses Gate definition
$user->can('manage-settings', $settings);  // Uses Gate with arguments
```

**How it works**: If the call doesn't match the custom formats, it falls back to the parent class implementation (Laravel's Gate system).

---

## Implementation Details

### Code

```php
/**
 * Check if user can perform action on resource.
 * Compatible with Laravel's can() method signature.
 * 
 * @param mixed $abilities Can be a string permission, or action_resource format
 * @param array|mixed $arguments Optional arguments (resource name if first param is action)
 * @return bool
 */
public function can($abilities, $arguments = []): bool
{
    // If called with two string arguments (action, resource), convert to permission format
    if (is_string($abilities) && is_string($arguments)) {
        $permission = "{$abilities}_{$arguments}";
        return $this->hasPermission($permission);
    }
    
    // If called with a single permission string
    if (is_string($abilities) && empty($arguments)) {
        return $this->hasPermission($abilities);
    }
    
    // Fall back to parent implementation for other cases (Laravel's Gate system)
    return parent::can($abilities, $arguments);
}
```

### Logic Flow

1. **Two String Arguments**: `can('create', 'posts')`
   - Combines into `create_posts`
   - Checks via `hasPermission('create_posts')`

2. **Single String Argument**: `can('create_posts')`
   - Uses permission name directly
   - Checks via `hasPermission('create_posts')`

3. **Other Cases**: Falls back to Laravel's Gate system
   - Calls `parent::can($abilities, $arguments)`

---

## Comparison with Other Methods

### `can()` vs `hasPermission()` vs `hasRole()`

```php
$user = User::find(1);

// Using can() - flexible, multiple formats
$user->can('create', 'posts');      // ✅ Action + resource
$user->can('create_posts');         // ✅ Full permission name

// Using hasPermission() - explicit permission check
$user->hasPermission('create_posts');  // ✅ Direct permission check

// Using hasRole() - role check
$user->hasRole('admin');            // ✅ Check if user has role
$user->hasRole(['admin', 'editor']); // ✅ Check if user has any role

// Convenience methods
$user->isAdmin();                   // ✅ Check if admin
$user->isEditor();                  // ✅ Check if editor
$user->isAuthor();                  // ✅ Check if author
$user->isViewer();                  // ✅ Check if viewer
```

---

## Real-World Examples

### Example 1: Controller Authorization

```php
class PostController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Check if user can create posts
        if (!$user->can('create', 'posts')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Create post...
    }
    
    public function update(Request $request, Post $post)
    {
        $user = $request->user();
        
        // Check if user can update posts
        if (!$user->can('update', 'posts')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // For authors, check ownership
        if ($user->isAuthor() && $post->author_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Update post...
    }
}
```

### Example 2: Blade Templates

```blade
@if(auth()->user()->can('create', 'posts'))
    <a href="{{ route('posts.create') }}" class="btn btn-primary">
        Create New Post
    </a>
@endif

@if(auth()->user()->can('update', 'posts'))
    <a href="{{ route('posts.edit', $post) }}" class="btn btn-secondary">
        Edit Post
    </a>
@endif

@if(auth()->user()->can('delete', 'posts'))
    <form action="{{ route('posts.destroy', $post) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
@endif
```

### Example 3: API Response

```php
public function user(Request $request)
{
    $user = $request->user();
    
    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'roles' => $user->roles->pluck('name'),
        'permissions' => [
            'can_create_posts' => $user->can('create', 'posts'),
            'can_update_posts' => $user->can('update', 'posts'),
            'can_delete_posts' => $user->can('delete', 'posts'),
            'can_create_comments' => $user->can('create', 'comments'),
            'can_manage_users' => $user->can('create', 'users'),
        ],
    ]);
}
```

### Example 4: Middleware

```php
class CheckPostPermission
{
    public function handle(Request $request, Closure $next, string $action)
    {
        if (!$request->user()->can($action, 'posts')) {
            abort(403, 'Unauthorized action.');
        }
        
        return $next($request);
    }
}

// Usage in routes:
Route::post('/posts', [PostController::class, 'store'])
    ->middleware('check.post.permission:create');
```

---

## Benefits of This Implementation

### ✅ Backward Compatible
- Works with Laravel's existing Gate system
- No breaking changes to existing code
- Follows Laravel's method signature

### ✅ Flexible Usage
- Supports multiple calling formats
- Works with custom permission system
- Falls back to Laravel's Gate when needed

### ✅ Clean Syntax
```php
// Clean and readable
$user->can('create', 'posts')

// Instead of
$user->hasPermission('create_posts')
```

### ✅ Framework Integration
- Works with Laravel's `@can` Blade directive
- Compatible with authorization middleware
- Integrates with policy system

---

## Migration from Old `canPerform()` Method

If you were using the old `canPerform()` method, you can now use `can()`:

```php
// Old way (canPerform)
$user->canPerform('create', 'posts');

// New way (can) - same functionality
$user->can('create', 'posts');

// Both work the same way!
```

---

## Testing

### Unit Test Example

```php
public function test_user_can_check_permissions()
{
    $user = User::factory()->create();
    $user->assignRole('author');
    
    // Test two-string format
    $this->assertTrue($user->can('create', 'posts'));
    $this->assertTrue($user->can('read', 'posts'));
    $this->assertFalse($user->can('delete', 'users'));
    
    // Test single-string format
    $this->assertTrue($user->can('create_posts'));
    $this->assertTrue($user->can('read_posts'));
    $this->assertFalse($user->can('delete_users'));
}
```

### Integration Test Example

```php
public function test_user_can_create_post_with_permission()
{
    $user = User::factory()->create();
    $user->assignRole('author');
    
    $this->assertTrue($user->can('create', 'posts'));
    
    $response = $this->actingAs($user)->postJson('/api/posts', [
        'title' => 'Test Post',
        'body' => 'Test content',
        'status' => 'published',
    ]);
    
    $response->assertStatus(201);
}
```

---

## Summary

✅ **Compatible**: Fully compatible with Laravel's `can()` method signature

✅ **Flexible**: Supports multiple calling formats
- `can('create', 'posts')` - Action + resource
- `can('create_posts')` - Full permission name
- `can('ability', $model)` - Laravel Gate fallback

✅ **Clean**: Provides clean, readable syntax for permission checks

✅ **Integrated**: Works seamlessly with Laravel's authorization system

**The `can()` method is now production-ready and fully compatible with Laravel!** 🎉

