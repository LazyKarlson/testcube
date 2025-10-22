# Public API - Quick Reference

## Public Endpoints (No Authentication Required)

### Meta

| Endpoint | Method | Description | Rate Limit |
|----------|--------|-------------|------------|
| `/api/meta/roles` | GET | List all roles | 60/min |

### Posts

| Endpoint | Method | Description | Rate Limit |
|----------|--------|-------------|------------|
| `/api/posts` | GET | List all posts | 60/min |
| `/api/posts/search` | GET | Search posts | 60/min |
| `/api/posts/{id}` | GET | Get single post | 60/min |

### Comments

| Endpoint | Method | Description | Rate Limit |
|----------|--------|-------------|------------|
| `/api/posts/{post}/comments` | GET | List post comments | 60/min |
| `/api/comments/{id}` | GET | Get single comment | 60/min |

---

## Quick Examples

### Get All Roles
```bash
curl -X GET http://localhost:85/api/meta/roles
```

### List Posts
```bash
curl -X GET http://localhost:85/api/posts
```

### Search Posts
```bash
curl -X GET "http://localhost:85/api/posts/search?q=laravel&status=published"
```

### Get Single Post
```bash
curl -X GET http://localhost:85/api/posts/1
```

### Get Post Comments
```bash
curl -X GET http://localhost:85/api/posts/1/comments
```

### Get Single Comment
```bash
curl -X GET http://localhost:85/api/comments/1
```

---

## Rate Limiting

**Limit**: 60 requests per minute per IP address

**Headers in Response**:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1698765432
```

**When Exceeded** (429 Too Many Requests):
```json
{
  "message": "Too Many Requests"
}
```

**Retry-After Header**: Indicates seconds to wait

---

## JavaScript Example

```javascript
async function getPosts() {
  const response = await fetch('http://localhost:85/api/posts', {
    headers: {
      'Accept': 'application/json'
    }
  });

  // Check rate limit
  const remaining = response.headers.get('X-RateLimit-Remaining');
  console.log(`Requests remaining: ${remaining}`);

  if (response.status === 429) {
    const retryAfter = response.headers.get('Retry-After');
    throw new Error(`Rate limit exceeded. Retry after ${retryAfter}s`);
  }

  return await response.json();
}

// Usage
try {
  const posts = await getPosts();
  console.log(`Total posts: ${posts.total}`);
} catch (error) {
  console.error(error.message);
}
```

---

## Access Control

### ✅ Public (No Auth)
- List roles
- List posts
- Search posts
- View post
- List comments
- View comment

### ❌ Requires Authentication
- Create post/comment
- Update post/comment
- Delete post/comment
- View my posts
- User management
- Role management

---

## Best Practices

1. **Monitor Rate Limits**
   ```javascript
   const remaining = response.headers.get('X-RateLimit-Remaining');
   if (remaining < 10) console.warn('Approaching rate limit');
   ```

2. **Handle 429 Errors**
   ```javascript
   if (response.status === 429) {
     const retryAfter = response.headers.get('Retry-After');
     await new Promise(r => setTimeout(r, retryAfter * 1000));
     // Retry request
   }
   ```

3. **Cache Responses**
   ```javascript
   const cache = new Map();
   const cacheKey = 'posts_page_1';
   if (cache.has(cacheKey)) return cache.get(cacheKey);
   const data = await fetchPosts();
   cache.set(cacheKey, data);
   ```

4. **Use Pagination**
   ```bash
   # Fetch larger pages to reduce requests
   curl "http://localhost:85/api/posts?per_page=50"
   ```

---

## Testing

### Test Public Access
```bash
# No authentication needed
curl -X GET http://localhost:85/api/posts | jq '.total'
```

### Test Rate Limit
```bash
# Make 61 requests to trigger rate limit
for i in {1..61}; do
  curl -s -o /dev/null -w "%{http_code}\n" http://localhost:85/api/posts
done
```

### Check Rate Limit Headers
```bash
curl -I http://localhost:85/api/posts | grep -i "x-ratelimit"
```

---

## Upgrading to Authenticated Access

For higher rate limits and write access:

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
  -H "Authorization: Bearer {token}"
```

---

## Error Responses

### 429 Too Many Requests
```json
{
  "message": "Too Many Requests"
}
```
**Solution**: Wait for the time specified in `Retry-After` header

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Post] 999"
}
```
**Solution**: Check that the resource ID exists

---

**Full Documentation**: See `PUBLIC_API_ACCESS.md`

