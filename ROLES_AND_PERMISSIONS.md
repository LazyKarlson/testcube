# Roles and Permissions Documentation

This application implements a comprehensive Role-Based Access Control (RBAC) system with three predefined roles and granular permissions.

## Roles Overview

### 1. **Admin**
- Full access to all resources
- Can CRUD (Create, Read, Update, Delete) posts, comments, and users
- Can modify ANY user's posts and comments
- Can assign/remove roles to/from users
- **Permissions**: All permissions

### 2. **Editor**
- Can CRUD posts and comments
- Can modify ANY user's posts and comments
- Cannot manage users or roles
- **Permissions**:
  - `create_posts`, `read_posts`, `update_posts`, `delete_posts`
  - `create_comments`, `read_comments`, `update_comments`, `delete_comments`

### 3. **Author**
- Can CRUD only their OWN posts and comments
- Cannot modify other users' content
- Cannot manage users or roles
- **Permissions**:
  - `create_posts`, `read_posts`, `update_posts`, `delete_posts`
  - `create_comments`, `read_comments`, `update_comments`, `delete_comments`
- **Note**: Ownership is enforced in controllers

### 4. **Viewer**
- Read-only access to posts and comments
- Cannot create, update, or delete any resources
- **Permissions**:
  - `read_posts`, `read_comments`

## Default Role Assignment

- New users are automatically assigned the **author** role upon registration
- Admins can change user roles via the API
- Common upgrade path: author → editor → admin
- Admins can downgrade users to viewer if needed

## Permissions List

### Post Permissions
- `create_posts` - Create new posts
- `read_posts` - View posts
- `update_posts` - Edit posts
- `delete_posts` - Delete posts

### Comment Permissions
- `create_comments` - Create new comments
- `read_comments` - View comments
- `update_comments` - Edit comments
- `delete_comments` - Delete comments

### User Permissions (Admin only)
- `create_users` - Create new users
- `read_users` - View user details
- `update_users` - Edit user information
- `delete_users` - Delete users

## API Endpoints

### Authentication Endpoints

All authentication endpoints return user roles in the response.

**Register** (assigns author role by default):
```bash
POST /api/register
```

**Login** (returns user with roles):
```bash
POST /api/login
```

**Get Current User** (includes roles and permissions):
```bash
GET /api/user
```

---

### Role Management Endpoints

#### Get All Roles
```bash
GET /api/roles
Authorization: Bearer {token}
```

**Response:**
```json
{
  "roles": [
    {
      "id": 1,
      "name": "admin",
      "description": "Administrator with full access",
      "permissions": ["create_posts", "read_posts", ...]
    },
    ...
  ]
}
```

#### Get User's Roles
```bash
GET /api/users/{user_id}/roles
Authorization: Bearer {token}
```

#### Assign Role to User (Admin Only)
```bash
POST /api/users/{user_id}/roles
Authorization: Bearer {token}
Content-Type: application/json

{
  "role": "editor"
}
```

#### Remove Role from User (Admin Only)
```bash
DELETE /api/users/{user_id}/roles
Authorization: Bearer {token}
Content-Type: application/json

{
  "role": "editor"
}
```

---

### Post Endpoints

#### List All Posts (All authenticated users)
```bash
GET /api/posts
Authorization: Bearer {token}
```

#### Get Single Post (All authenticated users)
```bash
GET /api/posts/{post_id}
Authorization: Bearer {token}
```

#### Create Post (Requires: create_posts permission)
```bash
POST /api/posts
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "My Post Title",
  "body": "Post content here...",
  "status": "published"
}
```

**Allowed roles**: Admin, Editor, Author

**Note**:
- `title` must be unique
- `status` can be "draft" or "published" (defaults to "draft")
- `published_at` is auto-set when status is "published"

#### Update Post (Requires: update_posts permission)
```bash
PUT /api/posts/{post_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Title",
  "body": "Updated content...",
  "status": "published"
}
```

**Allowed roles**:
- Admin (can update any post)
- Editor (can update any post)
- Author (can only update own posts)

#### Delete Post (Requires: delete_posts permission)
```bash
DELETE /api/posts/{post_id}
Authorization: Bearer {token}
```

**Allowed roles**:
- Admin (can delete any post)
- Editor (can delete any post)
- Author (can only delete own posts)

#### Get My Posts
```bash
GET /api/my-posts
Authorization: Bearer {token}
```

---

### Comment Endpoints

#### List Comments for a Post (All authenticated users)
```bash
GET /api/posts/{post_id}/comments
Authorization: Bearer {token}
```

#### Get Single Comment (All authenticated users)
```bash
GET /api/comments/{comment_id}
Authorization: Bearer {token}
```

#### Create Comment (Requires: create_comments permission)
```bash
POST /api/posts/{post_id}/comments
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "This is my comment"
}
```

**Allowed roles**: Admin, Editor, Author

#### Update Comment (Requires: update_comments permission)
```bash
PUT /api/comments/{comment_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Updated comment"
}
```

**Allowed roles**:
- Admin (can update any comment)
- Editor (can update any comment)
- Author (can only update own comments)

#### Delete Comment (Requires: delete_comments permission)
```bash
DELETE /api/comments/{comment_id}
Authorization: Bearer {token}
```

**Allowed roles**:
- Admin (can delete any comment)
- Editor (can delete any comment)
- Author (can only delete own comments)

---

### User Management Endpoints (Admin Only)

#### List All Users
```bash
GET /api/users
Authorization: Bearer {token}
```

**Required role**: Admin

#### Get User Details
```bash
GET /api/users/{user_id}
Authorization: Bearer {token}
```

**Required role**: Admin

#### Update User
```bash
PUT /api/users/{user_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Updated Name",
  "email": "newemail@example.com",
  "password": "newpassword123"
}
```

**Required role**: Admin

#### Delete User
```bash
DELETE /api/users/{user_id}
Authorization: Bearer {token}
```

**Required role**: Admin
**Note**: Users cannot delete themselves

---

## Database Schema

### Tables

1. **roles**
   - `id`, `name`, `description`, `timestamps`

2. **permissions**
   - `id`, `name`, `description`, `timestamps`

3. **role_user** (pivot)
   - `id`, `role_id`, `user_id`, `timestamps`

4. **permission_role** (pivot)
   - `id`, `permission_id`, `role_id`, `timestamps`

5. **posts**
   - `id`, `user_id`, `title` (unique), `body`, `status` (enum: draft/published), `published_at` (nullable), `timestamps`

6. **comments**
   - `id`, `user_id`, `post_id`, `content`, `timestamps`

---

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Roles and Permissions
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

Or seed everything (includes test users):
```bash
php artisan db:seed
```

This creates:
- 4 roles (admin, editor, author, viewer)
- 12 permissions
- 4 test users:
  - `admin@example.com` / `password` (admin role)
  - `editor@example.com` / `password` (editor role)
  - `author@example.com` / `password` (author role)
  - `viewer@example.com` / `password` (viewer role)

---

## Usage Examples

### Example 1: Viewer trying to create a post (FORBIDDEN)
```bash
curl -X POST http://localhost:85/api/posts \
  -H "Authorization: Bearer {viewer_token}" \
  -H "Content-Type: application/json" \
  -d '{"title": "Test", "content": "Test content"}'
```

**Response (403)**:
```json
{
  "message": "Forbidden. You do not have the required permission.",
  "required_permission": "create_posts"
}
```

### Example 2: Author creating a post (SUCCESS)
```bash
curl -X POST http://localhost:85/api/posts \
  -H "Authorization: Bearer {author_token}" \
  -H "Content-Type: application/json" \
  -d '{"title": "My Post", "content": "Post content", "published": true}'
```

**Response (201)**:
```json
{
  "message": "Post created successfully",
  "post": {
    "id": 1,
    "title": "My Post",
    "content": "Post content",
    "published": true,
    "user": {...}
  }
}
```

### Example 3: Author trying to edit another user's post (FORBIDDEN)
```bash
curl -X PUT http://localhost:85/api/posts/5 \
  -H "Authorization: Bearer {author_token}" \
  -H "Content-Type: application/json" \
  -d '{"title": "Hacked!"}'
```

**Response (403)**:
```json
{
  "message": "Forbidden. You can only update your own posts."
}
```

### Example 4: Admin assigning author role to a user
```bash
curl -X POST http://localhost:85/api/users/5/roles \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{"role": "author"}'
```

---

## Helper Methods

### User Model Methods

```php
// Check if user has a role
$user->hasRole('admin'); // true/false
$user->hasRole(['admin', 'editor']); // true if has any

// Check if user has permission
$user->hasPermission('create_posts'); // true/false
$user->can('create', 'posts'); // true/false

// Assign/remove roles
$user->assignRole('editor');
$user->removeRole('viewer');

// Convenience methods
$user->isAdmin(); // true/false
$user->isEditor(); // true/false
$user->isAuthor(); // true/false
$user->isViewer(); // true/false
```

### Role Model Methods

```php
// Check if role has permission
$role->hasPermission('create_posts'); // true/false

// Give permission to role
$role->givePermissionTo('create_posts');
```

---

## Middleware Usage

### In Routes
```php
// Require specific role
Route::middleware('role:admin')->group(function () {
    // Admin only routes
});

// Require any of multiple roles
Route::middleware('role:admin,editor,author')->group(function () {
    // Admin, editor, or author routes
});

// Require specific permission
Route::middleware('permission:create_posts')->group(function () {
    // Routes requiring create_posts permission
});
```

### In Controllers
```php
public function __construct()
{
    $this->middleware('role:admin')->only(['destroy']);
    $this->middleware('permission:create_posts')->only(['store']);
}
```

---

## Testing

### Test with different roles:

1. **Login as admin**:
```bash
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'
```

2. **Login as editor**:
```bash
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "editor@example.com", "password": "password"}'
```

3. **Login as author**:
```bash
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "author@example.com", "password": "password"}'
```

4. **Login as viewer**:
```bash
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "viewer@example.com", "password": "password"}'
```

Use the returned token to test different endpoints and verify permissions!

---

## Security Notes

1. **Ownership checks**: Authors can only update/delete their own posts and comments. Editors and admins can modify any content.
2. **Self-deletion prevention**: Users cannot delete their own account
3. **Default role**: New registrations get viewer role by default
4. **Permission-based**: All actions are checked against permissions, not just roles
5. **Middleware protection**: Routes are protected at the middleware level
6. **Role hierarchy**: Viewer < Author < Editor < Admin (in terms of permissions)

---

## Troubleshooting

### Permission denied errors
- Check user's roles: `GET /api/user`
- Verify role has the required permission: `GET /api/roles`
- Ensure middleware is applied correctly in routes

### Role not assigned
- Run seeders: `php artisan db:seed`
- Check database: roles and permissions tables should be populated
- Verify user-role relationship in `role_user` table

