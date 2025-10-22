# Posts List API - Quick Reference

## Endpoint
```
GET /api/posts
```

## Authentication
```
Authorization: Bearer {token}
```

## Query Parameters

| Parameter | Default | Options | Description |
|-----------|---------|---------|-------------|
| `sort_by` | `published_at` | `published_at`, `title`, `created_at` | Sort field |
| `sort_order` | `desc` | `asc`, `desc` | Sort direction |
| `per_page` | `25` | 1-100 | Items per page |
| `page` | `1` | 1+ | Page number |

## Quick Examples

### Default Request
```bash
curl -X GET http://localhost:85/api/posts \
  -H "Authorization: Bearer {token}"
```

### Sort by Title (A-Z)
```bash
curl -X GET "http://localhost:85/api/posts?sort_by=title&sort_order=asc" \
  -H "Authorization: Bearer {token}"
```

### Sort by Creation Date
```bash
curl -X GET "http://localhost:85/api/posts?sort_by=created_at&sort_order=desc" \
  -H "Authorization: Bearer {token}"
```

### Custom Pagination
```bash
curl -X GET "http://localhost:85/api/posts?per_page=50&page=2" \
  -H "Authorization: Bearer {token}"
```

## Response Structure

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "title": "Post Title",
      "status": "published",
      "body": "Full post content...",
      "created_at": "2025-10-15T10:30:00.000000Z",
      "published_at": "2025-10-15T14:00:00.000000Z",
      "author": {
        "name": "John Doe",
        "email": "john@example.com"
      },
      "comments_count": 15,
      "last_comment": {
        "body": "Great post!",
        "author_name": "Jane Smith"
      }
    }
  ],
  "total": 87,
  "per_page": 25,
  "last_page": 4,
  "next_page_url": "http://localhost:85/api/posts?page=2"
}
```

## Response Fields

### Post Object
- `id` - Post ID
- `title` - Post title
- `status` - `draft` or `published`
- `body` - Full post content
- `created_at` - Creation timestamp
- `published_at` - Publication timestamp (null for drafts)
- `author.name` - Author's name
- `author.email` - Author's email
- `comments_count` - Total comments
- `last_comment.body` - Last comment text (null if no comments)
- `last_comment.author_name` - Last comment author

### Pagination
- `current_page` - Current page number
- `data` - Array of posts
- `total` - Total posts count
- `per_page` - Items per page
- `last_page` - Total pages
- `next_page_url` - Next page URL
- `prev_page_url` - Previous page URL

## JavaScript Example

```javascript
async function fetchPosts(page = 1, sortBy = 'published_at') {
  const params = new URLSearchParams({
    page,
    sort_by: sortBy,
    sort_order: 'desc',
    per_page: 25
  });

  const response = await fetch(`/api/posts?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  return await response.json();
}

// Usage
const data = await fetchPosts(1, 'title');
console.log(`Total posts: ${data.total}`);
data.data.forEach(post => {
  console.log(`${post.title} by ${post.author.name}`);
});
```

## Common Use Cases

### Blog Homepage (Recent Posts)
```
GET /api/posts?sort_by=published_at&sort_order=desc&per_page=10
```

### Admin Dashboard (All Posts)
```
GET /api/posts?sort_by=created_at&sort_order=desc&per_page=50
```

### Alphabetical List
```
GET /api/posts?sort_by=title&sort_order=asc&per_page=100
```

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```
**Fix**: Provide valid Bearer token

### 422 Validation Error
```json
{
  "message": "The sort by field must be one of: published_at, title, created_at.",
  "errors": {
    "sort_by": ["The sort by field must be one of: published_at, title, created_at."]
  }
}
```
**Fix**: Use valid parameter values

## Testing

```bash
# Get auth token
TOKEN=$(curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"author@example.com","password":"password"}' \
  | jq -r '.token')

# Test endpoint
curl -X GET http://localhost:85/api/posts \
  -H "Authorization: Bearer $TOKEN" | jq

# Test sorting
curl -X GET "http://localhost:85/api/posts?sort_by=title&sort_order=asc" \
  -H "Authorization: Bearer $TOKEN" | jq '.data[].title'

# Test pagination
curl -X GET "http://localhost:85/api/posts?per_page=5" \
  -H "Authorization: Bearer $TOKEN" | jq '.per_page, .total'
```

## Performance Tips

✅ Use reasonable `per_page` values (25-50)  
✅ Cache results on client side  
✅ Use pagination instead of loading all posts  
✅ Leverage browser/CDN caching for public posts  

## Related Endpoints

- `GET /api/posts/{id}` - Single post
- `GET /api/my-posts` - Current user's posts
- `POST /api/posts` - Create post
- `PUT /api/posts/{id}` - Update post
- `DELETE /api/posts/{id}` - Delete post

---

**Full Documentation**: See `POSTS_LIST_ENDPOINT.md`

