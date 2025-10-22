# API Reference Card

## 🌐 Base URL

```
http://localhost:85/api
```

---

## 🔐 Authentication

All authenticated endpoints require Bearer token in header:

```
Authorization: Bearer {your_token_here}
```

---

## 📋 Quick Reference

### Authentication Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/register` | ❌ | Register new user |
| `POST` | `/login` | ❌ | Login user |
| `POST` | `/logout` | ✅ | Logout user |
| `GET` | `/user` | ✅ | Get authenticated user |
| `POST` | `/email/verification-notification` | ✅ | Send verification email |
| `GET` | `/email/verify/{id}/{hash}` | ❌ | Verify email |
| `GET` | `/email/verification-status` | ✅ | Check verification status |

### Posts Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/posts` | ❌ | List posts (paginated) |
| `GET` | `/posts/{id}` | ❌ | Get single post |
| `GET` | `/posts/search` | ❌ | Search posts |
| `POST` | `/posts` | ✅ | Create post |
| `PUT` | `/posts/{id}` | ✅ | Update post |
| `DELETE` | `/posts/{id}` | ✅ | Delete post |
| `GET` | `/my-posts` | ✅ | Get user's posts |

### Comments Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/posts/{post}/comments` | ❌ | List comments |
| `GET` | `/comments/{id}` | ❌ | Get single comment |
| `POST` | `/posts/{post}/comments` | ✅ | Create comment |
| `PUT` | `/comments/{id}` | ✅ | Update comment |
| `DELETE` | `/comments/{id}` | ✅ | Delete comment |

### Statistics Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/stats/posts` | ❌ | Post statistics |
| `GET` | `/stats/comments` | ❌ | Comment statistics |
| `GET` | `/stats/users` | ❌ | User statistics |

### Roles & Users Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/meta/roles` | ❌ | Get all roles |
| `GET` | `/users` | ✅ Admin | List users |
| `GET` | `/users/{id}` | ✅ Admin | Get user |
| `POST` | `/users/{user}/roles` | ✅ Admin | Assign role |
| `DELETE` | `/users/{user}/roles` | ✅ Admin | Remove role |

---

## 📝 Detailed Endpoints

### Register User

```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
  "message": "User registered successfully...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "roles": ["author"],
    "created_at": "2025-10-22T10:00:00.000000Z"
  },
  "access_token": "1|abc123...",
  "token_type": "Bearer"
}
```

---

### Login

```http
POST /api/login
Content-Type: application/json

{
  "email": "admin1@example.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Admin User 1",
    "email": "admin1@example.com",
    "roles": ["admin"]
  },
  "access_token": "2|xyz789...",
  "token_type": "Bearer"
}
```

---

### Get Authenticated User

```http
GET /api/user
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "Admin User 1",
    "email": "admin1@example.com",
    "email_verified_at": "2025-10-22T10:00:00.000000Z",
    "roles": ["admin"],
    "permissions": ["create_posts", "read_posts", ...],
    "created_at": "2025-10-22T10:00:00.000000Z",
    "updated_at": "2025-10-22T10:00:00.000000Z"
  }
}
```

---

### List Posts

```http
GET /api/posts?page=1&per_page=25&sort_by=published_at&sort_order=desc
```

**Query Parameters:**
- `page` (optional) - Page number (default: 1)
- `per_page` (optional) - Items per page (default: 25, max: 100)
- `sort_by` (optional) - Sort field: `published_at`, `created_at`, `title` (default: `published_at`)
- `sort_order` (optional) - Sort order: `asc`, `desc` (default: `desc`)

**Response (200):**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "title": "Post Title",
      "body": "Post content...",
      "status": "published",
      "published_at": "2025-10-22T10:00:00.000000Z",
      "author": {
        "name": "Author Name",
        "email": "author@example.com"
      },
      "comments_count": 15,
      "last_comment": {
        "body": "Latest comment...",
        "created_at": "2025-10-22T11:00:00.000000Z"
      }
    }
  ],
  "total": 100,
  "per_page": 25,
  "last_page": 4
}
```

---

### Search Posts

```http
GET /api/posts/search?q=laravel&status=published&published_at[from]=2025-01-01
```

**Query Parameters:**
- `q` (required) - Search query (searches title and body)
- `status` (optional) - Filter by status: `draft`, `published`
- `published_at[from]` (optional) - Published after date (YYYY-MM-DD)
- `published_at[to]` (optional) - Published before date (YYYY-MM-DD)
- `page` (optional) - Page number
- `per_page` (optional) - Items per page

**Response (200):**
```json
{
  "query": "laravel",
  "filters": {
    "status": "published",
    "published_at": {
      "from": "2025-01-01",
      "to": null
    }
  },
  "results": {
    "current_page": 1,
    "data": [...],
    "total": 25
  }
}
```

---

### Create Post

```http
POST /api/posts
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "My New Post",
  "body": "This is the post content...",
  "status": "published"
}
```

**Response (201):**
```json
{
  "message": "Post created successfully",
  "post": {
    "id": 101,
    "title": "My New Post",
    "body": "This is the post content...",
    "status": "published",
    "published_at": "2025-10-22T10:00:00.000000Z",
    "author_id": 1,
    "created_at": "2025-10-22T10:00:00.000000Z"
  }
}
```

---

### Create Comment

```http
POST /api/posts/1/comments
Authorization: Bearer {token}
Content-Type: application/json

{
  "body": "Great post! Thanks for sharing."
}
```

**Response (201):**
```json
{
  "message": "Comment created successfully",
  "comment": {
    "id": 501,
    "body": "Great post! Thanks for sharing.",
    "post_id": 1,
    "author_id": 1,
    "created_at": "2025-10-22T10:00:00.000000Z"
  }
}
```

---

### Get Post Statistics

```http
GET /api/stats/posts?date_from=2025-01-01&date_to=2025-12-31
```

**Response (200):**
```json
{
  "total_posts": 250,
  "posts_by_status": {
    "draft": 50,
    "published": 200
  },
  "posts_by_date_range": 180,
  "average_comments_per_post": 12.5,
  "top_commented_posts": [
    {
      "id": 1,
      "title": "Popular Post",
      "comments_count": 50,
      "author": "Author Name"
    }
  ]
}
```

---

### Get Comment Statistics

```http
GET /api/stats/comments
```

**Response (200):**
```json
{
  "total_comments": 12625,
  "comments_by_date_range": 12625,
  "activity_by_hour": {
    "0": 500,
    "1": 450,
    ...
    "23": 520
  },
  "activity_by_weekday": {
    "Monday": 1800,
    "Tuesday": 1750,
    ...
  },
  "activity_by_date_last_30_days": [
    {
      "date": "2025-10-22",
      "count": 150
    }
  ]
}
```

---

### Get User Statistics

```http
GET /api/stats/users
```

**Response (200):**
```json
{
  "total_users": 57,
  "users_by_role": {
    "admin": 2,
    "editor": 5,
    "author": 50,
    "viewer": 0
  },
  "users_without_roles": 0,
  "top_authors_by_posts": [
    {
      "id": 1,
      "name": "Author User 1",
      "email": "author1@example.com",
      "posts_count": 10
    }
  ],
  "top_users_by_comments": [...],
  "average_posts_per_user": 4.82,
  "average_comments_per_user": 221.49
}
```

---

### Get All Roles

```http
GET /api/meta/roles
```

**Response (200):**
```json
{
  "roles": [
    {
      "id": 1,
      "name": "admin",
      "description": "Administrator with full access",
      "permissions": [
        "create_posts",
        "read_posts",
        "update_posts",
        "delete_posts",
        ...
      ]
    }
  ]
}
```

---

## 🔒 Error Responses

### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden

```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found

```json
{
  "message": "Resource not found."
}
```

### 422 Validation Error

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

### 429 Too Many Requests

```json
{
  "message": "Too Many Attempts."
}
```

**Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
Retry-After: 60
```

---

## 📊 Rate Limits

| Endpoint Type | Limit | Window |
|---------------|-------|--------|
| **Public endpoints** | 60 requests | 1 minute |
| **Authenticated endpoints** | Standard Laravel throttling | - |

**Public endpoints with rate limiting:**
- `GET /api/posts`
- `GET /api/posts/{id}`
- `GET /api/posts/search`
- `GET /api/posts/{post}/comments`
- `GET /api/comments/{id}`
- `GET /api/stats/*`
- `GET /api/meta/roles`

---

## 🎯 Quick Test Commands

```bash
# Register
curl -X POST http://localhost:85/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# Login
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin1@example.com","password":"password"}'

# Get posts
curl http://localhost:85/api/posts | jq

# Get statistics
curl http://localhost:85/api/stats/posts | jq

# Create post (replace {token})
curl -X POST http://localhost:85/api/posts \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Post","body":"Content here","status":"published"}'
```

---

**For complete documentation, see README.md** 📚

