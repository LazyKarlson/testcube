# Quick Start: Roles & Permissions

## Setup (One-time)

```bash
# Run migrations
php artisan migrate

# Seed roles, permissions, and test users
php artisan db:seed
```

## Test Users Created

| Email | Password | Role | Can Do |
|-------|----------|------|--------|
| admin@example.com | password | Admin | Everything (all posts/comments) |
| editor@example.com | password | Editor | CRUD all posts & comments |
| author@example.com | password | Author | CRUD own posts & comments only |
| viewer@example.com | password | Viewer | Read posts & comments only |

## Quick Test Flow

### 1. Login as Viewer
```bash
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "viewer@example.com", "password": "password"}'
```

Save the token from response.

### 2. Try to Create Post (Should FAIL - 403)
```bash
curl -X POST http://localhost:85/api/posts \
  -H "Authorization: Bearer {viewer_token}" \
  -H "Content-Type: application/json" \
  -d '{"title": "Test", "content": "Test"}'
```

### 3. Login as Author
```bash
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "author@example.com", "password": "password"}'
```

### 4. Create Post as Author (Should SUCCEED - 201)
```bash
curl -X POST http://localhost:85/api/posts \
  -H "Authorization: Bearer {author_token}" \
  -H "Content-Type: application/json" \
  -d '{"title": "My First Post", "content": "Hello World!", "published": true}'
```

### 5. Login as Admin
```bash
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'
```

### 6. Promote Viewer to Author
```bash
curl -X POST http://localhost:85/api/users/4/roles \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{"role": "author"}'
```

## Permission Matrix

| Action | Admin | Editor | Author | Viewer |
|--------|-------|--------|--------|--------|
| Read posts | ✅ | ✅ | ✅ | ✅ |
| Create posts | ✅ | ✅ | ✅ | ❌ |
| Update own posts | ✅ | ✅ | ✅ | ❌ |
| Update any posts | ✅ | ✅ | ❌ | ❌ |
| Delete own posts | ✅ | ✅ | ✅ | ❌ |
| Delete any posts | ✅ | ✅ | ❌ | ❌ |
| Read comments | ✅ | ✅ | ✅ | ✅ |
| Create comments | ✅ | ✅ | ✅ | ❌ |
| Update own comments | ✅ | ✅ | ✅ | ❌ |
| Update any comments | ✅ | ✅ | ❌ | ❌ |
| Delete own comments | ✅ | ✅ | ✅ | ❌ |
| Delete any comments | ✅ | ✅ | ❌ | ❌ |
| Manage users | ✅ | ❌ | ❌ | ❌ |
| Assign roles | ✅ | ❌ | ❌ | ❌ |

## Common API Calls

### Check Your Permissions
```bash
GET /api/user
Authorization: Bearer {token}
```

Returns your roles and permissions.

### List All Roles
```bash
GET /api/roles
Authorization: Bearer {token}
```

### View All Posts
```bash
GET /api/posts
Authorization: Bearer {token}
```

### Create a Post (Author/Editor/Admin only)
```bash
POST /api/posts
Authorization: Bearer {token}
{
  "title": "Post Title",
  "body": "Post content",
  "status": "published"
}
```

### Add Comment (Author/Editor/Admin only)
```bash
POST /api/posts/{post_id}/comments
Authorization: Bearer {token}
{
  "content": "Great post!"
}
```

### Manage Users (Admin only)
```bash
# List users
GET /api/users

# Assign role
POST /api/users/{user_id}/roles
{"role": "editor"}

# Remove role
DELETE /api/users/{user_id}/roles
{"role": "viewer"}
```

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```
**Solution**: Include valid Bearer token in Authorization header

### 403 Forbidden (Missing Role)
```json
{
  "message": "Forbidden. You do not have the required role.",
  "required_roles": ["admin"]
}
```
**Solution**: Contact admin to assign required role

### 403 Forbidden (Missing Permission)
```json
{
  "message": "Forbidden. You do not have the required permission.",
  "required_permission": "create_posts"
}
```
**Solution**: Contact admin to assign role with required permission

## New User Registration

New users automatically get **author** role:

```bash
POST /api/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Response includes:
```json
{
  "user": {
    "roles": ["author"]
  }
}
```

Admin can then upgrade the role to editor/admin or downgrade to viewer if needed.

