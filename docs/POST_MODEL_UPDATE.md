# Post Model Update - Field Changes

## ✅ Changes Made

The Post model, migration, and controller have been updated with the following field changes:

### Field Changes Summary

| Old Field | New Field | Type | Notes |
|-----------|-----------|------|-------|
| `user_id` | `author_id` | bigint (FK) | Renamed for semantic clarity |
| `content` | `body` | text | Renamed for clarity |
| `published` | `status` | enum('draft', 'published') | More flexible status system |
| N/A | `published_at` | timestamp (nullable) | Track when post was published |
| `title` | `title` | string (unique) | Now enforces uniqueness |

## 📝 Detailed Changes

### 1. Migration (`create_posts_table.php`)

**Before:**
```php
$table->foreignId('user_id')->constrained()->onDelete('cascade');
$table->string('title');
$table->text('content');
$table->boolean('published')->default(false);
```

**After:**
```php
$table->foreignId('author_id')->constrained('users')->onDelete('cascade');
$table->string('title')->unique();
$table->text('body');
$table->enum('status', ['draft', 'published'])->default('draft');
$table->timestamp('published_at')->nullable();
```

### 2. Model (`Post.php`)

**Before:**
```php
protected $fillable = ['user_id', 'title', 'content', 'published'];

protected $casts = [
    'published' => 'boolean',
];

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

**After:**
```php
protected $fillable = ['author_id', 'title', 'body', 'status', 'published_at'];

protected $casts = [
    'published_at' => 'datetime',
];

public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'author_id');
}

// Alias for backwards compatibility
public function user(): BelongsTo
{
    return $this->author();
}
```

**New Helper Methods Added:**
- `isPublished()` - Check if post is published
- `isDraft()` - Check if post is a draft
- `publish()` - Publish the post (sets status and published_at)
- `unpublish()` - Unpublish the post (sets to draft, clears published_at)

### 3. Controller (`PostController.php`)

**Store Method - Before:**
```php
$validated = $request->validate([
    'title' => 'required|string|max:255',
    'content' => 'required|string',
    'published' => 'boolean',
]);
```

**Store Method - After:**
```php
$validated = $request->validate([
    'title' => 'required|string|max:255|unique:posts,title',
    'body' => 'required|string',
    'status' => 'sometimes|in:draft,published',
    'published_at' => 'sometimes|nullable|date',
]);

// Auto-set published_at if status is published
if (isset($validated['status']) && $validated['status'] === 'published' && !isset($validated['published_at'])) {
    $validated['published_at'] = now();
}

// Default status to draft if not provided
if (!isset($validated['status'])) {
    $validated['status'] = 'draft';
}
```

**Update Method - Before:**
```php
$validated = $request->validate([
    'title' => 'sometimes|required|string|max:255',
    'content' => 'sometimes|required|string',
    'published' => 'sometimes|boolean',
]);
```

**Update Method - After:**
```php
$validated = $request->validate([
    'title' => 'sometimes|required|string|max:255|unique:posts,title,' . $post->id,
    'body' => 'sometimes|required|string',
    'status' => 'sometimes|in:draft,published',
    'published_at' => 'sometimes|nullable|date',
]);

// Auto-set published_at when changing status to published
if (isset($validated['status']) && $validated['status'] === 'published' && !$post->isPublished()) {
    if (!isset($validated['published_at'])) {
        $validated['published_at'] = now();
    }
}

// Clear published_at when changing status to draft
if (isset($validated['status']) && $validated['status'] === 'draft') {
    $validated['published_at'] = null;
}
```

## 🎯 Key Features

### 1. Unique Titles
- Post titles must now be unique across the entire posts table
- Validation enforces this at both creation and update
- Update validation excludes the current post ID to allow updating other fields

### 2. Status System
- Two statuses: `draft` and `published`
- Default status is `draft`
- More extensible than boolean (can add more statuses later like 'archived', 'pending', etc.)

### 3. Published Timestamp
- `published_at` tracks when a post was published
- Automatically set when status changes to 'published'
- Automatically cleared when status changes to 'draft'
- Nullable to support draft posts

### 4. Helper Methods
```php
// Check status
if ($post->isPublished()) {
    // Post is published
}

if ($post->isDraft()) {
    // Post is a draft
}

// Change status
$post->publish();  // Sets status to 'published' and published_at to now()
$post->unpublish(); // Sets status to 'draft' and clears published_at
```

## 📡 API Changes

### Create Post

**Old Request:**
```json
{
  "title": "My Post",
  "content": "Post content here",
  "published": true
}
```

**New Request:**
```json
{
  "title": "My Post",
  "body": "Post content here",
  "status": "published"
}
```

**Optional Fields:**
- `status` - Defaults to "draft" if not provided
- `published_at` - Auto-set if status is "published", can be manually set

### Update Post

**Old Request:**
```json
{
  "title": "Updated Title",
  "content": "Updated content",
  "published": false
}
```

**New Request:**
```json
{
  "title": "Updated Title",
  "body": "Updated content",
  "status": "draft"
}
```

### Response Format

**Old Response:**
```json
{
  "id": 1,
  "user_id": 1,
  "title": "My Post",
  "content": "Post content",
  "published": true,
  "created_at": "2025-10-22T10:00:00.000000Z",
  "updated_at": "2025-10-22T10:00:00.000000Z"
}
```

**New Response:**
```json
{
  "id": 1,
  "user_id": 1,
  "title": "My Post",
  "body": "Post content",
  "status": "published",
  "published_at": "2025-10-22T10:00:00.000000Z",
  "created_at": "2025-10-22T10:00:00.000000Z",
  "updated_at": "2025-10-22T10:00:00.000000Z"
}
```

## 🧪 Testing Examples

### Create a Draft Post
```bash
curl -X POST http://localhost:85/api/posts \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My Draft Post",
    "body": "This is a draft"
  }'
```

Response will have `"status": "draft"` and `"published_at": null`

### Create a Published Post
```bash
curl -X POST http://localhost:85/api/posts \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My Published Post",
    "body": "This is published",
    "status": "published"
  }'
```

Response will have `"status": "published"` and `"published_at": "2025-10-22T10:00:00.000000Z"`

### Publish a Draft
```bash
curl -X PUT http://localhost:85/api/posts/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "published"
  }'
```

The `published_at` field will be automatically set to the current timestamp.

### Unpublish a Post
```bash
curl -X PUT http://localhost:85/api/posts/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "draft"
  }'
```

The `published_at` field will be automatically cleared (set to null).

### Try to Create Duplicate Title (Will Fail)
```bash
curl -X POST http://localhost:85/api/posts \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My Published Post",
    "body": "Different content"
  }'
```

Response: `422 Unprocessable Entity` with validation error about duplicate title.

## 🔄 Migration Instructions

Since this changes the database schema, you need to run migrations:

```bash
cd src

# Drop all tables and re-run migrations with seeders
php artisan migrate:fresh --seed
```

**⚠️ Warning:** This will delete all existing data!

If you have production data, you'll need to create a migration to:
1. Add new columns (`body`, `status`, `published_at`)
2. Migrate data from old columns (`content` → `body`, `published` → `status`)
3. Drop old columns (`content`, `published`)
4. Add unique constraint to `title`

## 📊 Benefits of These Changes

### 1. Unique Titles
- ✅ Prevents duplicate content
- ✅ Better SEO (unique URLs based on titles)
- ✅ Clearer content organization

### 2. Status Enum vs Boolean
- ✅ More flexible (can add more statuses later)
- ✅ More readable code (`status === 'published'` vs `published === true`)
- ✅ Supports complex workflows (draft → review → published)

### 3. Published Timestamp
- ✅ Track publication history
- ✅ Sort by publication date
- ✅ Schedule posts (check if `published_at` is in the future)
- ✅ Analytics (know when content was published)

### 4. Field Naming
- ✅ `author_id` is more semantic than `user_id` for posts (indicates authorship)
- ✅ `body` is more semantic than `content` for blog posts
- ✅ Follows common conventions (title + body)
- ✅ Clearer distinction from other content types
- ✅ Maintains backwards compatibility with `user()` relationship alias

## 🚀 Next Steps

1. **Run migrations**: `php artisan migrate:fresh --seed`
2. **Update any frontend code** that references old field names
3. **Update API documentation** for consumers
4. **Test all CRUD operations** with new field names
5. **Consider adding scopes** to Post model:
   ```php
   public function scopePublished($query)
   {
       return $query->where('status', 'published');
   }
   
   public function scopeDraft($query)
   {
       return $query->where('status', 'draft');
   }
   ```

## 📚 Files Modified

1. ✅ `src/database/migrations/2025_10_22_094630_create_posts_table.php`
2. ✅ `src/app/Models/Post.php`
3. ✅ `src/app/Http/Controllers/Api/PostController.php`
4. ✅ `ROLES_AND_PERMISSIONS.md`
5. ✅ `QUICK_START_RBAC.md`
6. ✅ `IMPLEMENTATION_SUMMARY.md`
7. ✅ `AUTHOR_ROLE_UPDATE.md`
8. ✅ `POST_MODEL_UPDATE.md` (this file)

---

**All changes are complete!** The Post model now uses `body`, `status`, and `published_at` fields with unique titles.

