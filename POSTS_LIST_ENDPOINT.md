# Posts List Endpoint Documentation

## Overview

The posts list endpoint provides a paginated, sortable list of all posts with comprehensive information including author details, comment statistics, and the last comment.

## Endpoint

```
GET /api/posts
```

**Authentication**: Required (Bearer token)

**Access**: All authenticated users

---

## Query Parameters

| Parameter | Type | Default | Options | Description |
|-----------|------|---------|---------|-------------|
| `sort_by` | string | `published_at` | `published_at`, `title`, `created_at` | Field to sort by |
| `sort_order` | string | `desc` | `asc`, `desc` | Sort direction |
| `per_page` | integer | `25` | 1-100 | Number of posts per page |
| `page` | integer | `1` | 1+ | Page number |

---

## Response Format

### Success Response (200 OK)

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "title": "My First Blog Post",
      "status": "published",
      "body": "This is the full content of the blog post...",
      "created_at": "2025-10-15T10:30:00.000000Z",
      "published_at": "2025-10-15T14:00:00.000000Z",
      "author": {
        "name": "John Doe",
        "email": "john@example.com"
      },
      "comments_count": 15,
      "last_comment": {
        "body": "Great post! Thanks for sharing.",
        "author_name": "Jane Smith"
      }
    },
    {
      "id": 2,
      "title": "Another Post",
      "status": "draft",
      "body": "Draft content here...",
      "created_at": "2025-10-14T09:00:00.000000Z",
      "published_at": null,
      "author": {
        "name": "Alice Johnson",
        "email": "alice@example.com"
      },
      "comments_count": 0,
      "last_comment": null
    }
  ],
  "first_page_url": "http://localhost:85/api/posts?page=1",
  "from": 1,
  "last_page": 4,
  "last_page_url": "http://localhost:85/api/posts?page=4",
  "links": [
    {
      "url": null,
      "label": "&laquo; Previous",
      "active": false
    },
    {
      "url": "http://localhost:85/api/posts?page=1",
      "label": "1",
      "active": true
    },
    {
      "url": "http://localhost:85/api/posts?page=2",
      "label": "2",
      "active": false
    }
  ],
  "next_page_url": "http://localhost:85/api/posts?page=2",
  "path": "http://localhost:85/api/posts",
  "per_page": 25,
  "prev_page_url": null,
  "to": 25,
  "total": 87
}
```

### Response Fields

#### Post Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Post ID |
| `title` | string | Post title |
| `status` | string | Post status (`draft` or `published`) |
| `body` | string | Full post content |
| `created_at` | datetime | Post creation timestamp |
| `published_at` | datetime\|null | Publication timestamp (null for drafts) |
| `author` | object | Author information |
| `author.name` | string | Author's full name |
| `author.email` | string | Author's email address |
| `comments_count` | integer | Total number of comments on the post |
| `last_comment` | object\|null | Most recent comment (null if no comments) |
| `last_comment.body` | string | Comment text |
| `last_comment.author_name` | string | Comment author's name |

#### Pagination Metadata

| Field | Type | Description |
|-------|------|-------------|
| `current_page` | integer | Current page number |
| `data` | array | Array of post objects |
| `first_page_url` | string | URL to first page |
| `from` | integer | Starting item number on current page |
| `last_page` | integer | Total number of pages |
| `last_page_url` | string | URL to last page |
| `links` | array | Pagination links |
| `next_page_url` | string\|null | URL to next page |
| `path` | string | Base URL |
| `per_page` | integer | Items per page |
| `prev_page_url` | string\|null | URL to previous page |
| `to` | integer | Ending item number on current page |
| `total` | integer | Total number of posts |

---

## Usage Examples

### 1. Default Request (Published Date, Descending, 25 per page)

```bash
curl -X GET http://localhost:85/api/posts \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

### 2. Sort by Title (Ascending)

```bash
curl -X GET "http://localhost:85/api/posts?sort_by=title&sort_order=asc" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

### 3. Sort by Creation Date (Descending)

```bash
curl -X GET "http://localhost:85/api/posts?sort_by=created_at&sort_order=desc" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

### 4. Custom Pagination (50 per page)

```bash
curl -X GET "http://localhost:85/api/posts?per_page=50" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

### 5. Get Specific Page

```bash
curl -X GET "http://localhost:85/api/posts?page=3" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

### 6. Combined Parameters

```bash
curl -X GET "http://localhost:85/api/posts?sort_by=title&sort_order=asc&per_page=50&page=2" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

---

## JavaScript/Fetch Examples

### Basic Request

```javascript
const response = await fetch('http://localhost:85/api/posts', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  }
});

const data = await response.json();
console.log(data.data); // Array of posts
console.log(data.total); // Total posts count
```

### With Query Parameters

```javascript
const params = new URLSearchParams({
  sort_by: 'title',
  sort_order: 'asc',
  per_page: 50,
  page: 1
});

const response = await fetch(`http://localhost:85/api/posts?${params}`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  }
});

const data = await response.json();
```

### Pagination Handler

```javascript
async function fetchPosts(page = 1, sortBy = 'published_at', sortOrder = 'desc') {
  const params = new URLSearchParams({
    page: page,
    sort_by: sortBy,
    sort_order: sortOrder,
    per_page: 25
  });

  const response = await fetch(`http://localhost:85/api/posts?${params}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    }
  });

  if (!response.ok) {
    throw new Error('Failed to fetch posts');
  }

  return await response.json();
}

// Usage
const postsData = await fetchPosts(1, 'title', 'asc');
postsData.data.forEach(post => {
  console.log(`${post.title} by ${post.author.name}`);
  console.log(`Comments: ${post.comments_count}`);
});
```

---

## Error Responses

### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

**Cause**: Missing or invalid authentication token

### 422 Validation Error

```json
{
  "message": "The sort by field must be one of: published_at, title, created_at.",
  "errors": {
    "sort_by": [
      "The sort by field must be one of: published_at, title, created_at."
    ]
  }
}
```

**Cause**: Invalid query parameter value

**Common validation errors**:
- `sort_by` must be one of: `published_at`, `title`, `created_at`
- `sort_order` must be one of: `asc`, `desc`
- `per_page` must be between 1 and 100

---

## Sorting Behavior

### By Published Date (Default)

```
GET /api/posts?sort_by=published_at&sort_order=desc
```

- **Descending**: Newest published posts first
- **Ascending**: Oldest published posts first
- **Note**: Draft posts (with `published_at = null`) will appear at the end when sorting descending, at the beginning when sorting ascending

### By Title

```
GET /api/posts?sort_by=title&sort_order=asc
```

- **Ascending**: A-Z alphabetical order
- **Descending**: Z-A reverse alphabetical order

### By Creation Date

```
GET /api/posts?sort_by=created_at&sort_order=desc
```

- **Descending**: Most recently created first
- **Ascending**: Oldest created first

---

## Performance Considerations

### Optimizations Implemented

1. **Eager Loading**: Author and last comment are loaded with the posts to prevent N+1 queries
2. **Selective Fields**: Only necessary author fields (name, email) are loaded
3. **Limited Comments**: Only the most recent comment is loaded per post
4. **Indexed Sorting**: Database indexes on `published_at`, `created_at`, and `title` improve sort performance

### Recommended Practices

- Use reasonable `per_page` values (25-50) for best performance
- Avoid requesting very large page numbers
- Cache results on the client side when appropriate

---

## Use Cases

### 1. Blog Homepage
Display recent published posts:
```
GET /api/posts?sort_by=published_at&sort_order=desc&per_page=10
```

### 2. Admin Dashboard
Show all posts by creation date:
```
GET /api/posts?sort_by=created_at&sort_order=desc&per_page=50
```

### 3. Alphabetical Index
List posts alphabetically:
```
GET /api/posts?sort_by=title&sort_order=asc&per_page=100
```

### 4. Content Management
View drafts and published posts:
```
GET /api/posts?sort_by=created_at&sort_order=desc
```

---

## Related Endpoints

- `GET /api/posts/{id}` - Get single post details
- `GET /api/my-posts` - Get current user's posts
- `POST /api/posts` - Create new post
- `PUT /api/posts/{id}` - Update post
- `DELETE /api/posts/{id}` - Delete post
- `GET /api/posts/{id}/comments` - Get post comments

---

## Implementation Details

### Controller Method

**Location**: `src/app/Http/Controllers/Api/PostController.php`

**Method**: `index(Request $request)`

**Features**:
- Query parameter validation
- Dynamic sorting
- Configurable pagination
- Eager loading of relationships
- Comment count aggregation
- Response transformation

### Database Queries

The endpoint executes optimized queries:

```sql
-- Main query with joins and counts
SELECT posts.*, 
       COUNT(comments.id) as comments_count
FROM posts
LEFT JOIN comments ON posts.id = comments.post_id
ORDER BY published_at DESC
LIMIT 25 OFFSET 0;

-- Author eager loading
SELECT id, name, email FROM users WHERE id IN (...);

-- Last comment eager loading
SELECT * FROM comments 
WHERE post_id IN (...) 
ORDER BY created_at DESC 
LIMIT 1 PER post_id;
```

---

## Testing

### Manual Testing with cURL

```bash
# Get token first
TOKEN=$(curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"author@example.com","password":"password"}' \
  | jq -r '.token')

# Test default endpoint
curl -X GET http://localhost:85/api/posts \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq

# Test sorting by title
curl -X GET "http://localhost:85/api/posts?sort_by=title&sort_order=asc" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.data[].title'

# Test pagination
curl -X GET "http://localhost:85/api/posts?per_page=5&page=2" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.current_page, .per_page'
```

### Automated Testing

```php
public function test_posts_list_returns_paginated_results(): void
{
    $user = User::factory()->create();
    Post::factory()->count(30)->create();

    $response = $this->actingAs($user)->getJson('/api/posts');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'status',
                    'body',
                    'created_at',
                    'published_at',
                    'author' => ['name', 'email'],
                    'comments_count',
                    'last_comment',
                ]
            ],
            'total',
            'per_page',
        ]);

    $this->assertEquals(25, count($response->json('data')));
}

public function test_posts_can_be_sorted_by_title(): void
{
    $user = User::factory()->create();
    Post::factory()->create(['title' => 'Zebra Post']);
    Post::factory()->create(['title' => 'Apple Post']);

    $response = $this->actingAs($user)
        ->getJson('/api/posts?sort_by=title&sort_order=asc');

    $response->assertStatus(200);
    $titles = collect($response->json('data'))->pluck('title');
    $this->assertEquals('Apple Post', $titles->first());
}
```

---

**Endpoint is ready to use!** 🎉

