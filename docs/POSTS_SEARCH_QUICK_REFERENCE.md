# Posts Search API - Quick Reference

## Endpoint
```
GET /api/posts/search
```

## Authentication
```
Authorization: Bearer {token}
```

## Required Parameters

| Parameter | Description |
|-----------|-------------|
| `q` | Search query (min 1 char, case-insensitive) |

## Optional Parameters

| Parameter | Default | Options | Description |
|-----------|---------|---------|-------------|
| `status` | - | `draft`, `published` | Filter by status |
| `published_at[from]` | - | `Y-m-d` | Start date |
| `published_at[to]` | - | `Y-m-d` | End date |
| `sort_by` | `published_at` | `published_at`, `title`, `created_at` | Sort field |
| `sort_order` | `desc` | `asc`, `desc` | Sort direction |
| `per_page` | `25` | 1-100 | Items per page |
| `page` | `1` | 1+ | Page number |

## Quick Examples

### Basic Search
```bash
curl -X GET "http://localhost:85/api/posts/search?q=laravel" \
  -H "Authorization: Bearer {token}"
```

### Search Published Posts
```bash
curl -X GET "http://localhost:85/api/posts/search?q=tutorial&status=published" \
  -H "Authorization: Bearer {token}"
```

### Search with Date Range
```bash
curl -X GET "http://localhost:85/api/posts/search?q=php&published_at[from]=2025-10-01&published_at[to]=2025-10-31" \
  -H "Authorization: Bearer {token}"
```

### Search Draft Posts
```bash
curl -X GET "http://localhost:85/api/posts/search?q=work&status=draft" \
  -H "Authorization: Bearer {token}"
```

### Search with Sorting
```bash
curl -X GET "http://localhost:85/api/posts/search?q=api&sort_by=title&sort_order=asc" \
  -H "Authorization: Bearer {token}"
```

### Search with All Filters
```bash
curl -X GET "http://localhost:85/api/posts/search?q=laravel&status=published&published_at[from]=2025-10-01&published_at[to]=2025-10-31&sort_by=title&sort_order=asc&per_page=50" \
  -H "Authorization: Bearer {token}"
```

## Response Structure

```json
{
  "query": "laravel",
  "filters": {
    "status": "published",
    "published_from": "2025-10-01",
    "published_to": "2025-10-31"
  },
  "results": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Getting Started with Laravel",
        "status": "published",
        "body": "Full content...",
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
    "total": 42,
    "per_page": 25,
    "last_page": 2
  }
}
```

## Search Behavior

### Text Search (`q`)
- ✅ **Case-insensitive** - "Laravel" matches "laravel", "LARAVEL"
- ✅ **Searches both** title and body fields
- ✅ **Partial match** - "lar" matches "Laravel"
- ✅ **OR logic** - Matches if found in title OR body

### Status Filter
- `status=published` - Only published posts
- `status=draft` - Only draft posts
- Omit for all posts

### Date Range
- `published_at[from]` - Posts published on or after this date
- `published_at[to]` - Posts published on or before this date
- Can use independently or together
- Format: `Y-m-d` (e.g., `2025-10-15`)

## JavaScript Example

```javascript
async function searchPosts(query, filters = {}) {
  const params = new URLSearchParams({ q: query });
  
  if (filters.status) params.append('status', filters.status);
  if (filters.dateFrom) params.append('published_at[from]', filters.dateFrom);
  if (filters.dateTo) params.append('published_at[to]', filters.dateTo);
  if (filters.sortBy) params.append('sort_by', filters.sortBy);
  if (filters.sortOrder) params.append('sort_order', filters.sortOrder);
  if (filters.perPage) params.append('per_page', filters.perPage);

  const response = await fetch(`/api/posts/search?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  return await response.json();
}

// Usage
const results = await searchPosts('laravel', {
  status: 'published',
  dateFrom: '2025-10-01',
  dateTo: '2025-10-31',
  sortBy: 'title',
  sortOrder: 'asc'
});

console.log(`Found ${results.results.total} posts`);
```

## Common Use Cases

### Blog Search
```
GET /api/posts/search?q=laravel&status=published
```

### Find Drafts
```
GET /api/posts/search?q=todo&status=draft
```

### Monthly Archive
```
GET /api/posts/search?q=&published_at[from]=2025-10-01&published_at[to]=2025-10-31
```

### Recent Posts
```
GET /api/posts/search?q=announcement&published_at[from]=2025-10-01
```

### Content Audit
```
GET /api/posts/search?q=update&sort_by=created_at&sort_order=desc
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
  "message": "The q field is required.",
  "errors": {
    "q": ["The q field is required."]
  }
}
```
**Fix**: Provide required `q` parameter

## Testing

```bash
# Get token
TOKEN=$(curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"author@example.com","password":"password"}' \
  | jq -r '.token')

# Test search
curl -X GET "http://localhost:85/api/posts/search?q=laravel" \
  -H "Authorization: Bearer $TOKEN" | jq

# Test with filters
curl -X GET "http://localhost:85/api/posts/search?q=php&status=published&published_at[from]=2025-10-01" \
  -H "Authorization: Bearer $TOKEN" | jq '.results.total'
```

## Performance Tips

✅ Use specific search queries  
✅ Combine filters to narrow results  
✅ Use reasonable `per_page` values (25-50)  
✅ Cache frequent searches  
✅ Consider search result highlighting on frontend  

## Related Endpoints

- `GET /api/posts` - List all posts
- `GET /api/posts/{id}` - Single post
- `GET /api/my-posts` - Current user's posts

---

**Full Documentation**: See `POSTS_SEARCH_ENDPOINT.md`

