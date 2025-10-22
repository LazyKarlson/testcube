# Statistics Endpoints Documentation

## Overview

Three public statistics endpoints provide comprehensive analytics for posts, comments, and users. All endpoints are publicly accessible with rate limiting (60 requests/minute).

All statistics endpoints are handled by a single `StatsController` (`App\Http\Controllers\Api\StatsController`) for better organization and maintainability.

---

## Endpoints

| Endpoint | Method | Description | Rate Limit |
|----------|--------|-------------|------------|
| `/api/stats/posts` | GET | Post statistics | 60/min |
| `/api/stats/comments` | GET | Comment statistics | 60/min |
| `/api/stats/users` | GET | User statistics | 60/min |

---

## 1. Posts Statistics

### Endpoint
```
GET /api/stats/posts
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `date_from` | date (Y-m-d) | No | Start date for date range filter |
| `date_to` | date (Y-m-d) | No | End date for date range filter |

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `total_posts` | integer | Total number of posts |
| `posts_by_status` | object | Number of posts by status (draft/published) |
| `posts_by_date_range` | object\|null | Posts count within date range (if provided) |
| `average_comments_per_post` | float | Average number of comments per post |
| `top_commented_posts` | array | Top 5 most commented posts with details |

### Example Request

```bash
# Basic request
curl -X GET "http://localhost:85/api/stats/posts"

# With date range
curl -X GET "http://localhost:85/api/stats/posts?date_from=2024-01-01&date_to=2024-12-31"
```

### Example Response

```json
{
  "total_posts": 150,
  "posts_by_status": {
    "draft": 25,
    "published": 125
  },
  "posts_by_date_range": {
    "date_from": "2024-01-01",
    "date_to": "2024-12-31",
    "total": 100,
    "by_status": {
      "draft": 15,
      "published": 85
    }
  },
  "average_comments_per_post": 12.5,
  "top_commented_posts": [
    {
      "id": 42,
      "title": "Introduction to Laravel",
      "status": "published",
      "author": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "comments_count": 156,
      "created_at": "2024-03-15T10:30:00.000000Z",
      "published_at": "2024-03-15T14:00:00.000000Z"
    },
    {
      "id": 38,
      "title": "Advanced PHP Techniques",
      "status": "published",
      "author": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com"
      },
      "comments_count": 142,
      "created_at": "2024-02-20T09:15:00.000000Z",
      "published_at": "2024-02-20T12:00:00.000000Z"
    }
  ]
}
```

---

## 2. Comments Statistics

### Endpoint
```
GET /api/stats/comments
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `date_from` | date (Y-m-d) | No | Start date for date range filter |
| `date_to` | date (Y-m-d) | No | End date for date range filter |

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `total_comments` | integer | Total number of comments |
| `comments_by_date_range` | object\|null | Comments count within date range (if provided) |
| `activity_by_hour` | array | Comments count by hour (0-23) |
| `activity_by_weekday` | array | Comments count by weekday with day names |
| `activity_by_date_last_30_days` | array | Daily comment counts for last 30 days |

### Example Request

```bash
# Basic request
curl -X GET "http://localhost:85/api/stats/comments"

# With date range
curl -X GET "http://localhost:85/api/stats/comments?date_from=2024-01-01&date_to=2024-12-31"
```

### Example Response

```json
{
  "total_comments": 1875,
  "comments_by_date_range": {
    "date_from": "2024-01-01",
    "date_to": "2024-12-31",
    "total": 1500
  },
  "activity_by_hour": [
    12, 8, 5, 3, 2, 4, 10, 25, 45, 67, 89, 102,
    115, 98, 87, 76, 65, 54, 43, 32, 28, 22, 18, 15
  ],
  "activity_by_weekday": [
    {
      "day": "Sunday",
      "day_number": 0,
      "count": 245
    },
    {
      "day": "Monday",
      "day_number": 1,
      "count": 312
    },
    {
      "day": "Tuesday",
      "day_number": 2,
      "count": 298
    },
    {
      "day": "Wednesday",
      "day_number": 3,
      "count": 287
    },
    {
      "day": "Thursday",
      "day_number": 4,
      "count": 265
    },
    {
      "day": "Friday",
      "day_number": 5,
      "count": 234
    },
    {
      "day": "Saturday",
      "day_number": 6,
      "count": 234
    }
  ],
  "activity_by_date_last_30_days": [
    {
      "date": "2024-10-01",
      "count": 45
    },
    {
      "date": "2024-10-02",
      "count": 52
    }
  ]
}
```

---

## 3. Users Statistics

### Endpoint
```
GET /api/stats/users
```

### Query Parameters

None.

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `total_users` | integer | Total number of users |
| `users_by_role` | object | Number of users by role |
| `users_without_roles` | integer | Number of users without any role |
| `top_authors_by_posts` | array | Top 5 users by number of posts |
| `top_users_by_comments` | array | Top 5 users by number of comments |
| `average_posts_per_user` | float | Average number of posts per user |
| `average_comments_per_user` | float | Average number of comments per user |

### Example Request

```bash
curl -X GET "http://localhost:85/api/stats/users"
```

### Example Response

```json
{
  "total_users": 250,
  "users_by_role": {
    "admin": 5,
    "editor": 15,
    "author": 180,
    "viewer": 50
  },
  "users_without_roles": 0,
  "top_authors_by_posts": [
    {
      "id": 42,
      "name": "John Doe",
      "email": "john@example.com",
      "roles": ["author", "editor"],
      "posts_count": 87
    },
    {
      "id": 15,
      "name": "Jane Smith",
      "email": "jane@example.com",
      "roles": ["author"],
      "posts_count": 65
    }
  ],
  "top_users_by_comments": [
    {
      "id": 23,
      "name": "Bob Johnson",
      "email": "bob@example.com",
      "roles": ["viewer"],
      "comments_count": 342
    },
    {
      "id": 42,
      "name": "John Doe",
      "email": "john@example.com",
      "roles": ["author", "editor"],
      "comments_count": 298
    }
  ],
  "average_posts_per_user": 0.6,
  "average_comments_per_user": 7.5
}
```

---

## Usage Examples

### JavaScript/Fetch

```javascript
// Get post statistics
async function getPostStats(dateFrom = null, dateTo = null) {
  const params = new URLSearchParams();
  if (dateFrom) params.append('date_from', dateFrom);
  if (dateTo) params.append('date_to', dateTo);
  
  const url = `http://localhost:85/api/stats/posts${params.toString() ? '?' + params : ''}`;
  const response = await fetch(url);
  return await response.json();
}

// Get comment statistics
async function getCommentStats() {
  const response = await fetch('http://localhost:85/api/stats/comments');
  return await response.json();
}

// Get user statistics
async function getUserStats() {
  const response = await fetch('http://localhost:85/api/stats/users');
  return await response.json();
}

// Usage
const postStats = await getPostStats('2024-01-01', '2024-12-31');
console.log(`Total posts: ${postStats.total_posts}`);
console.log(`Published: ${postStats.posts_by_status.published}`);
console.log(`Drafts: ${postStats.posts_by_status.draft}`);
```

### Python

```python
import requests

# Get post statistics
def get_post_stats(date_from=None, date_to=None):
    params = {}
    if date_from:
        params['date_from'] = date_from
    if date_to:
        params['date_to'] = date_to
    
    response = requests.get('http://localhost:85/api/stats/posts', params=params)
    return response.json()

# Get comment statistics
def get_comment_stats():
    response = requests.get('http://localhost:85/api/stats/comments')
    return response.json()

# Get user statistics
def get_user_stats():
    response = requests.get('http://localhost:85/api/stats/users')
    return response.json()

# Usage
post_stats = get_post_stats('2024-01-01', '2024-12-31')
print(f"Total posts: {post_stats['total_posts']}")
print(f"Average comments per post: {post_stats['average_comments_per_post']}")
```

---

## Rate Limiting

All statistics endpoints are rate-limited to **60 requests per minute** per IP address.

### Rate Limit Headers

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

### Rate Limit Exceeded Response

```json
{
  "message": "Too Many Attempts."
}
```

**Status Code**: `429 Too Many Requests`

---

## Error Responses

### Invalid Date Format

```json
{
  "message": "The date from field must be a valid date.",
  "errors": {
    "date_from": [
      "The date from field must be a valid date."
    ]
  }
}
```

**Status Code**: `422 Unprocessable Entity`

### Date Range Validation

```json
{
  "message": "The date to field must be a date after or equal to date from.",
  "errors": {
    "date_to": [
      "The date to field must be a date after or equal to date from."
    ]
  }
}
```

**Status Code**: `422 Unprocessable Entity`

---

## Use Cases

### 1. Dashboard Analytics

Display key metrics on an admin dashboard:

```javascript
async function loadDashboard() {
  const [posts, comments, users] = await Promise.all([
    fetch('/api/stats/posts').then(r => r.json()),
    fetch('/api/stats/comments').then(r => r.json()),
    fetch('/api/stats/users').then(r => r.json())
  ]);
  
  document.getElementById('total-posts').textContent = posts.total_posts;
  document.getElementById('total-comments').textContent = comments.total_comments;
  document.getElementById('total-users').textContent = users.total_users;
}
```

### 2. Activity Charts

Create charts showing comment activity:

```javascript
async function createActivityChart() {
  const stats = await fetch('/api/stats/comments').then(r => r.json());
  
  // Use with Chart.js or similar
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: stats.activity_by_weekday.map(d => d.day),
      datasets: [{
        label: 'Comments by Weekday',
        data: stats.activity_by_weekday.map(d => d.count)
      }]
    }
  });
}
```

### 3. Leaderboards

Display top contributors:

```javascript
async function showLeaderboard() {
  const stats = await fetch('/api/stats/users').then(r => r.json());
  
  const topAuthors = stats.top_authors_by_posts;
  const html = topAuthors.map((author, index) => `
    <div class="leaderboard-item">
      <span class="rank">#${index + 1}</span>
      <span class="name">${author.name}</span>
      <span class="count">${author.posts_count} posts</span>
    </div>
  `).join('');
  
  document.getElementById('leaderboard').innerHTML = html;
}
```

---

## Summary

✅ **Three comprehensive statistics endpoints**:
- Posts statistics with status breakdown and top posts
- Comments statistics with time-based activity analysis
- User statistics with role distribution and top contributors

✅ **Features**:
- Public access (no authentication required)
- Rate limiting (60 requests/minute)
- Date range filtering for posts and comments
- Detailed activity breakdowns
- Top 5 rankings

✅ **Use cases**:
- Admin dashboards
- Analytics reports
- Activity charts
- Leaderboards
- Trend analysis

**All statistics endpoints are ready for production use!** 🎉

