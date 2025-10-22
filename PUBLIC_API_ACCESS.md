# Public API Access & Rate Limiting

## Overview

The API provides public read-only access to posts and comments without requiring authentication. These public endpoints are protected by rate limiting to prevent abuse.

---

## Public Endpoints

### Meta (Read-Only)

Metadata endpoints for application information:

| Endpoint | Method | Description | Rate Limit |
|----------|--------|-------------|------------|
| `/api/meta/roles` | GET | List all roles with permissions | 60/min |

### Posts (Read-Only)

All post read endpoints are publicly accessible:

| Endpoint | Method | Description | Rate Limit |
|----------|--------|-------------|------------|
| `/api/posts` | GET | List all posts with pagination | 60/min |
| `/api/posts/search` | GET | Search posts with filters | 60/min |
| `/api/posts/{id}` | GET | Get single post details | 60/min |

### Comments (Read-Only)

All comment read endpoints are publicly accessible:

| Endpoint | Method | Description | Rate Limit |
|----------|--------|-------------|------------|
| `/api/posts/{post}/comments` | GET | List comments for a post | 60/min |
| `/api/comments/{id}` | GET | Get single comment details | 60/min |

### Statistics (Read-Only)

All statistics endpoints are publicly accessible:

| Endpoint | Method | Description | Rate Limit |
|----------|--------|-------------|------------|
| `/api/stats/posts` | GET | Post statistics and analytics | 60/min |
| `/api/stats/comments` | GET | Comment statistics and activity | 60/min |
| `/api/stats/users` | GET | User statistics and rankings | 60/min |

---

## Rate Limiting

### Configuration

**Rate Limit**: 60 requests per minute per IP address

**Applies to**:
- All public read-only endpoints (posts and comments)

**Does NOT apply to**:
- Authenticated endpoints (different rate limits may apply)
- Registration and login endpoints

### Rate Limit Headers

Every response from rate-limited endpoints includes these headers:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1698765432
```

| Header | Description |
|--------|-------------|
| `X-RateLimit-Limit` | Maximum requests allowed per minute |
| `X-RateLimit-Remaining` | Requests remaining in current window |
| `X-RateLimit-Reset` | Unix timestamp when the limit resets |

### Rate Limit Exceeded Response

When the rate limit is exceeded, the API returns:

**Status Code**: `429 Too Many Requests`

**Response**:
```json
{
  "message": "Too Many Requests"
}
```

**Headers**:
```
Retry-After: 42
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1698765432
```

---

## Usage Examples

### Public Access (No Authentication)

#### Get All Roles
```bash
curl -X GET http://localhost:85/api/meta/roles \
  -H "Accept: application/json"
```

#### List Posts
```bash
curl -X GET http://localhost:85/api/posts \
  -H "Accept: application/json"
```

#### Search Posts
```bash
curl -X GET "http://localhost:85/api/posts/search?q=laravel&status=published" \
  -H "Accept: application/json"
```

#### Get Single Post
```bash
curl -X GET http://localhost:85/api/posts/1 \
  -H "Accept: application/json"
```

#### Get Post Comments
```bash
curl -X GET http://localhost:85/api/posts/1/comments \
  -H "Accept: application/json"
```

#### Get Single Comment
```bash
curl -X GET http://localhost:85/api/comments/1 \
  -H "Accept: application/json"
```

#### Get Post Statistics
```bash
curl -X GET http://localhost:85/api/stats/posts \
  -H "Accept: application/json"

# With date range
curl -X GET "http://localhost:85/api/stats/posts?date_from=2024-01-01&date_to=2024-12-31" \
  -H "Accept: application/json"
```

#### Get Comment Statistics
```bash
curl -X GET http://localhost:85/api/stats/comments \
  -H "Accept: application/json"
```

#### Get User Statistics
```bash
curl -X GET http://localhost:85/api/stats/users \
  -H "Accept: application/json"
```

### Authenticated Access (Optional)

Authenticated users can still access these endpoints with their token:

```bash
curl -X GET http://localhost:85/api/posts \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Note**: Authenticated requests may have different (higher) rate limits.

---

## JavaScript Examples

### Basic Fetch (Public)

```javascript
async function getPosts(page = 1) {
  const response = await fetch(`http://localhost:85/api/posts?page=${page}`, {
    headers: {
      'Accept': 'application/json'
    }
  });

  if (response.status === 429) {
    const retryAfter = response.headers.get('Retry-After');
    throw new Error(`Rate limit exceeded. Retry after ${retryAfter} seconds`);
  }

  if (!response.ok) {
    throw new Error('Failed to fetch posts');
  }

  return await response.json();
}

// Usage
try {
  const posts = await getPosts(1);
  console.log(`Total posts: ${posts.total}`);
} catch (error) {
  console.error(error.message);
}
```

### With Rate Limit Handling

```javascript
async function fetchWithRateLimit(url) {
  const response = await fetch(url, {
    headers: {
      'Accept': 'application/json'
    }
  });

  // Check rate limit headers
  const limit = response.headers.get('X-RateLimit-Limit');
  const remaining = response.headers.get('X-RateLimit-Remaining');
  const reset = response.headers.get('X-RateLimit-Reset');

  console.log(`Rate limit: ${remaining}/${limit} remaining`);

  if (response.status === 429) {
    const retryAfter = response.headers.get('Retry-After');
    throw new Error(`Rate limit exceeded. Retry after ${retryAfter} seconds`);
  }

  return await response.json();
}

// Usage
const posts = await fetchWithRateLimit('http://localhost:85/api/posts');
```

### Search with Error Handling

```javascript
async function searchPosts(query, filters = {}) {
  const params = new URLSearchParams({ q: query, ...filters });
  
  try {
    const response = await fetch(`http://localhost:85/api/posts/search?${params}`, {
      headers: {
        'Accept': 'application/json'
      }
    });

    if (response.status === 429) {
      const retryAfter = response.headers.get('Retry-After');
      return {
        error: 'rate_limit',
        message: `Too many requests. Please wait ${retryAfter} seconds.`,
        retryAfter: parseInt(retryAfter)
      };
    }

    if (!response.ok) {
      throw new Error('Search failed');
    }

    return await response.json();
  } catch (error) {
    return {
      error: 'network',
      message: error.message
    };
  }
}

// Usage
const results = await searchPosts('laravel', { status: 'published' });
if (results.error) {
  console.error(results.message);
} else {
  console.log(`Found ${results.results.total} posts`);
}
```

---

## Access Control Summary

### Public Access (No Authentication Required)

✅ **Allowed**:
- List all roles (`GET /api/meta/roles`)
- List all posts (`GET /api/posts`)
- Search posts (`GET /api/posts/search`)
- View single post (`GET /api/posts/{id}`)
- List post comments (`GET /api/posts/{post}/comments`)
- View single comment (`GET /api/comments/{id}`)
- View post statistics (`GET /api/stats/posts`)
- View comment statistics (`GET /api/stats/comments`)
- View user statistics (`GET /api/stats/users`)

❌ **Not Allowed** (Requires Authentication):
- Create posts
- Update posts
- Delete posts
- Create comments
- Update comments
- Delete comments
- View user's own posts (`/api/my-posts`)
- User management
- Role management

### Rate Limits

| Access Type | Rate Limit | Scope |
|-------------|------------|-------|
| Public (unauthenticated) | 60 requests/minute | Per IP address |
| Authenticated | Default Laravel limits | Per user |

---

## Best Practices

### For Public API Consumers

1. **Monitor Rate Limit Headers**
   ```javascript
   const remaining = response.headers.get('X-RateLimit-Remaining');
   if (remaining < 10) {
     console.warn('Approaching rate limit');
   }
   ```

2. **Implement Retry Logic**
   ```javascript
   async function fetchWithRetry(url, maxRetries = 3) {
     for (let i = 0; i < maxRetries; i++) {
       const response = await fetch(url);
       
       if (response.status === 429) {
         const retryAfter = response.headers.get('Retry-After');
         await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
         continue;
       }
       
       return response;
     }
     throw new Error('Max retries exceeded');
   }
   ```

3. **Cache Responses**
   ```javascript
   const cache = new Map();
   
   async function getCachedPosts(page) {
     const cacheKey = `posts_${page}`;
     
     if (cache.has(cacheKey)) {
       return cache.get(cacheKey);
     }
     
     const posts = await fetchPosts(page);
     cache.set(cacheKey, posts);
     
     // Clear cache after 5 minutes
     setTimeout(() => cache.delete(cacheKey), 5 * 60 * 1000);
     
     return posts;
   }
   ```

4. **Batch Requests**
   - Avoid making rapid sequential requests
   - Use pagination efficiently
   - Fetch larger pages when appropriate (`per_page` parameter)

5. **Handle Errors Gracefully**
   ```javascript
   try {
     const posts = await fetchPosts();
   } catch (error) {
     if (error.message.includes('Rate limit')) {
       // Show user-friendly message
       showNotification('Please wait a moment before trying again');
     } else {
       // Handle other errors
       showError('Failed to load posts');
     }
   }
   ```

---

## Testing Rate Limits

### Test Rate Limit Enforcement

```bash
# Make 61 requests rapidly to trigger rate limit
for i in {1..61}; do
  echo "Request $i"
  curl -X GET http://localhost:85/api/posts \
    -H "Accept: application/json" \
    -w "\nStatus: %{http_code}\n" \
    -s -o /dev/null
done
```

### Check Rate Limit Headers

```bash
curl -X GET http://localhost:85/api/posts \
  -H "Accept: application/json" \
  -I | grep -i "x-ratelimit"
```

Expected output:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

### Test Recovery After Rate Limit

```bash
# Trigger rate limit
for i in {1..61}; do
  curl -s -o /dev/null http://localhost:85/api/posts
done

# Wait 60 seconds
echo "Waiting 60 seconds..."
sleep 60

# Try again (should work)
curl -X GET http://localhost:85/api/posts \
  -H "Accept: application/json" | jq '.total'
```

---

## CORS Configuration

For public API access from web browsers, ensure CORS is properly configured.

**Laravel CORS Configuration** (`config/cors.php`):

```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_origins' => ['*'], // Or specify allowed domains
    'allowed_headers' => ['*'],
    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

---

## Security Considerations

### Rate Limiting Benefits

1. **Prevents Abuse**: Limits excessive requests from single IP
2. **Protects Resources**: Prevents server overload
3. **Fair Usage**: Ensures all users get fair access
4. **DDoS Mitigation**: Helps mitigate simple DDoS attacks

### Additional Security Measures

1. **IP-based Rate Limiting**: Current implementation
2. **Consider Adding**:
   - User-agent based filtering
   - Geographic restrictions if needed
   - API key system for higher limits
   - Authenticated users get higher limits

### Monitoring

Monitor rate limit violations:

```bash
# Check Laravel logs for rate limit hits
tail -f storage/logs/laravel.log | grep "429"
```

---

## Upgrading to Authenticated Access

Users who need higher rate limits or write access should register and authenticate:

### 1. Register
```bash
curl -X POST http://localhost:85/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 2. Login
```bash
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### 3. Use Token
```bash
curl -X GET http://localhost:85/api/posts \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## Summary

✅ **Public Access**:
- Posts (list, search, view)
- Comments (list, view)
- No authentication required

✅ **Rate Limiting**:
- 60 requests per minute per IP
- Rate limit headers in every response
- 429 status when exceeded

✅ **Best Practices**:
- Monitor rate limit headers
- Implement retry logic
- Cache responses
- Handle errors gracefully

✅ **Security**:
- IP-based rate limiting
- Prevents abuse
- Fair usage for all users

**Public API is ready for use!** 🌐

