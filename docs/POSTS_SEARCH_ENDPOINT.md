# Posts Search Endpoint Documentation

## Overview

The posts search endpoint provides powerful search capabilities with case-insensitive text search, status filtering, date range filtering, and sorting options.

## Endpoint

```
GET /api/posts/search
```

**Authentication**: Required (Bearer token)

**Access**: All authenticated users

---

## Query Parameters

| Parameter | Type | Required | Options/Format | Description |
|-----------|------|----------|----------------|-------------|
| `q` | string | **Yes** | Min 1 char | Search query (searches title and body) |
| `status` | string | No | `draft`, `published` | Filter by post status |
| `published_at[from]` | date | No | `Y-m-d` (e.g., `2025-10-01`) | Start date for published_at range |
| `published_at[to]` | date | No | `Y-m-d` (e.g., `2025-10-31`) | End date for published_at range |
| `sort_by` | string | No | `published_at`, `title`, `created_at` | Field to sort by (default: `published_at`) |
| `sort_order` | string | No | `asc`, `desc` | Sort direction (default: `desc`) |
| `per_page` | integer | No | 1-100 | Posts per page (default: 25) |
| `page` | integer | No | 1+ | Page number (default: 1) |

---

## Search Behavior

### Text Search (`q` parameter)
- **Case-insensitive**: Searches are not case-sensitive
- **Fields searched**: Both `title` and `body` fields
- **Match type**: Partial match (substring search)
- **Logic**: Returns posts where the query appears in title OR body

**Examples**:
- `q=laravel` - Finds posts with "Laravel", "LARAVEL", "laravel" in title or body
- `q=getting started` - Finds posts containing "getting started" (case-insensitive)

### Status Filter (`status` parameter)
- Filter posts by their publication status
- Options: `draft` or `published`
- If omitted, returns posts of all statuses

### Date Range Filter (`published_at[from]` and `published_at[to]`)
- Filter posts by publication date range
- Both parameters are optional and can be used independently
- Format: `Y-m-d` (e.g., `2025-10-15`)
- `published_at[from]`: Posts published on or after this date
- `published_at[to]`: Posts published on or before this date
- Validation: `to` date must be equal to or after `from` date

---

## Response Format

### Success Response (200 OK)

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
        "body": "Laravel is a web application framework...",
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
    "first_page_url": "http://localhost:85/api/posts/search?page=1",
    "from": 1,
    "last_page": 2,
    "last_page_url": "http://localhost:85/api/posts/search?page=2",
    "next_page_url": "http://localhost:85/api/posts/search?page=2",
    "path": "http://localhost:85/api/posts/search",
    "per_page": 25,
    "prev_page_url": null,
    "to": 25,
    "total": 42
  }
}
```

### Response Structure

#### Top Level
| Field | Type | Description |
|-------|------|-------------|
| `query` | string | The search query that was executed |
| `filters` | object | Applied filters (status, date range) |
| `results` | object | Paginated search results |

#### Filters Object
| Field | Type | Description |
|-------|------|-------------|
| `status` | string\|null | Status filter applied |
| `published_from` | string\|null | Start date filter applied |
| `published_to` | string\|null | End date filter applied |

#### Results Object (Pagination)
Standard Laravel pagination structure with post data.

#### Post Object (in results.data)
| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Post ID |
| `title` | string | Post title |
| `status` | string | Post status (`draft` or `published`) |
| `body` | string | Full post content |
| `created_at` | datetime | Post creation timestamp |
| `published_at` | datetime\|null | Publication timestamp |
| `author.name` | string | Author's name |
| `author.email` | string | Author's email |
| `comments_count` | integer | Total comments count |
| `last_comment` | object\|null | Most recent comment |
| `last_comment.body` | string | Comment text |
| `last_comment.author_name` | string | Comment author's name |

---

## Usage Examples

### 1. Basic Search

Search for posts containing "laravel":

```bash
curl -X GET "http://localhost:85/api/posts/search?q=laravel" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 2. Search with Status Filter

Search for published posts containing "tutorial":

```bash
curl -X GET "http://localhost:85/api/posts/search?q=tutorial&status=published" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 3. Search with Date Range

Search for posts containing "php" published in October 2025:

```bash
curl -X GET "http://localhost:85/api/posts/search?q=php&published_at[from]=2025-10-01&published_at[to]=2025-10-31" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 4. Search with All Filters

Search for published posts containing "laravel" from October 2025, sorted by title:

```bash
curl -X GET "http://localhost:85/api/posts/search?q=laravel&status=published&published_at[from]=2025-10-01&published_at[to]=2025-10-31&sort_by=title&sort_order=asc" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 5. Search Draft Posts Only

Search for draft posts containing "work in progress":

```bash
curl -X GET "http://localhost:85/api/posts/search?q=work%20in%20progress&status=draft" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 6. Search with Custom Pagination

Search with 50 results per page:

```bash
curl -X GET "http://localhost:85/api/posts/search?q=api&per_page=50&page=1" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 7. Search Posts from Specific Date Onwards

Search for posts containing "update" published from October 15, 2025:

```bash
curl -X GET "http://localhost:85/api/posts/search?q=update&published_at[from]=2025-10-15" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 8. Search Posts Until Specific Date

Search for posts containing "announcement" published until October 20, 2025:

```bash
curl -X GET "http://localhost:85/api/posts/search?q=announcement&published_at[to]=2025-10-20" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## JavaScript/Fetch Examples

### Basic Search

```javascript
async function searchPosts(query, filters = {}) {
  const params = new URLSearchParams({
    q: query,
    ...filters
  });

  const response = await fetch(`http://localhost:85/api/posts/search?${params}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    }
  });

  if (!response.ok) {
    throw new Error('Search failed');
  }

  return await response.json();
}

// Usage
const results = await searchPosts('laravel', {
  status: 'published',
  sort_by: 'title',
  sort_order: 'asc'
});

console.log(`Found ${results.results.total} posts`);
results.results.data.forEach(post => {
  console.log(`- ${post.title} by ${post.author.name}`);
});
```

### Advanced Search with Date Range

```javascript
async function advancedSearch(query, options = {}) {
  const params = new URLSearchParams({ q: query });

  // Add status filter
  if (options.status) {
    params.append('status', options.status);
  }

  // Add date range
  if (options.dateFrom) {
    params.append('published_at[from]', options.dateFrom);
  }
  if (options.dateTo) {
    params.append('published_at[to]', options.dateTo);
  }

  // Add sorting
  if (options.sortBy) {
    params.append('sort_by', options.sortBy);
  }
  if (options.sortOrder) {
    params.append('sort_order', options.sortOrder);
  }

  // Add pagination
  if (options.perPage) {
    params.append('per_page', options.perPage);
  }
  if (options.page) {
    params.append('page', options.page);
  }

  const response = await fetch(`/api/posts/search?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  return await response.json();
}

// Usage: Search published Laravel posts from October 2025
const results = await advancedSearch('laravel', {
  status: 'published',
  dateFrom: '2025-10-01',
  dateTo: '2025-10-31',
  sortBy: 'published_at',
  sortOrder: 'desc',
  perPage: 25
});
```

### Search Component Example

```javascript
class PostSearch {
  constructor(apiUrl, token) {
    this.apiUrl = apiUrl;
    this.token = token;
  }

  async search(query, filters = {}) {
    const params = new URLSearchParams({ q: query });

    Object.entries(filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined) {
        if (key === 'dateFrom') {
          params.append('published_at[from]', value);
        } else if (key === 'dateTo') {
          params.append('published_at[to]', value);
        } else {
          params.append(key, value);
        }
      }
    });

    const response = await fetch(`${this.apiUrl}/posts/search?${params}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Search failed');
    }

    return await response.json();
  }
}

// Usage
const postSearch = new PostSearch('http://localhost:85/api', token);

const results = await postSearch.search('laravel', {
  status: 'published',
  dateFrom: '2025-10-01',
  dateTo: '2025-10-31',
  sort_by: 'title',
  sort_order: 'asc',
  per_page: 50
});

console.log(`Query: ${results.query}`);
console.log(`Total results: ${results.results.total}`);
console.log(`Applied filters:`, results.filters);
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
  "message": "The q field is required.",
  "errors": {
    "q": [
      "The q field is required."
    ]
  }
}
```

**Common validation errors**:
- `q` is required and must be at least 1 character
- `status` must be one of: `draft`, `published`
- `published_at.from` must be a valid date
- `published_at.to` must be a valid date and after or equal to `published_at.from`
- `sort_by` must be one of: `published_at`, `title`, `created_at`
- `sort_order` must be one of: `asc`, `desc`
- `per_page` must be between 1 and 100

---

## Use Cases

### 1. Blog Search Feature
Allow users to search blog posts:
```
GET /api/posts/search?q=laravel&status=published
```

### 2. Admin Content Management
Search all posts (including drafts):
```
GET /api/posts/search?q=migration&sort_by=created_at&sort_order=desc
```

### 3. Monthly Archives
Find posts from a specific month:
```
GET /api/posts/search?q=&published_at[from]=2025-10-01&published_at[to]=2025-10-31
```

### 4. Draft Management
Find draft posts containing specific keywords:
```
GET /api/posts/search?q=todo&status=draft
```

### 5. Recent Published Content
Search recent published posts:
```
GET /api/posts/search?q=announcement&status=published&published_at[from]=2025-10-01
```

---

## Performance Considerations

### Optimizations Implemented

1. **Case-insensitive search**: Uses database-level `LOWER()` function
2. **Eager loading**: Prevents N+1 queries for authors and comments
3. **Selective fields**: Only loads necessary author fields
4. **Limited comments**: Only loads the most recent comment
5. **Indexed fields**: Database indexes on searchable and sortable fields

### Best Practices

- ✅ Use specific search queries (avoid very short queries like single characters)
- ✅ Combine filters to narrow results
- ✅ Use reasonable `per_page` values (25-50)
- ✅ Cache frequent searches on the client side
- ✅ Consider implementing search result highlighting on the frontend

### Database Indexes

For optimal performance, ensure these indexes exist:

```sql
CREATE INDEX idx_posts_title_lower ON posts (LOWER(title));
CREATE INDEX idx_posts_status ON posts (status);
CREATE INDEX idx_posts_published_at ON posts (published_at);
CREATE INDEX idx_posts_created_at ON posts (created_at);
```

---

## Testing

### Manual Testing with cURL

```bash
# Get authentication token
TOKEN=$(curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"author@example.com","password":"password"}' \
  | jq -r '.token')

# Basic search
curl -X GET "http://localhost:85/api/posts/search?q=laravel" \
  -H "Authorization: Bearer $TOKEN" | jq

# Search with status filter
curl -X GET "http://localhost:85/api/posts/search?q=tutorial&status=published" \
  -H "Authorization: Bearer $TOKEN" | jq '.results.total'

# Search with date range
curl -X GET "http://localhost:85/api/posts/search?q=php&published_at[from]=2025-10-01&published_at[to]=2025-10-31" \
  -H "Authorization: Bearer $TOKEN" | jq '.results.data[].title'

# Search with all filters
curl -X GET "http://localhost:85/api/posts/search?q=api&status=published&published_at[from]=2025-10-01&sort_by=title&sort_order=asc&per_page=10" \
  -H "Authorization: Bearer $TOKEN" | jq
```

---

## Related Endpoints

- `GET /api/posts` - List all posts (with pagination and sorting)
- `GET /api/posts/{id}` - Get single post details
- `GET /api/my-posts` - Get current user's posts
- `POST /api/posts` - Create new post
- `PUT /api/posts/{id}` - Update post
- `DELETE /api/posts/{id}` - Delete post

---

**Search endpoint is ready to use!** 🔍

