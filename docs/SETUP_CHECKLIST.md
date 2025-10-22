# Setup Checklist

## ✅ Complete Setup Verification

Use this checklist to ensure your Laravel Blog API is properly set up and running.

---

## 📋 Pre-Setup Checklist

### Prerequisites

- [ ] Docker installed (version 19+)
- [ ] Docker Compose installed
- [ ] Git installed
- [ ] Terminal/Command line access
- [ ] Text editor (VS Code, Sublime, etc.)

---

## 🚀 Initial Setup Checklist

### Step 1: Clone & Configure

- [ ] Repository cloned to local machine
- [ ] Navigated to project directory (`cd testcube`)
- [ ] Copied `.env.example` to `.env`
- [ ] Edited `.env` with database credentials:
  - [ ] `POSTGRES_DB` set
  - [ ] `POSTGRES_USER` set
  - [ ] `POSTGRES_PASSWORD` set

**Example `.env`:**
```env
POSTGRES_DB=laravel_db
POSTGRES_USER=laravel_user
POSTGRES_PASSWORD=secret_password
```

---

### Step 2: Docker Setup

- [ ] Built Docker containers (`make build`)
- [ ] Started Docker containers (`make up`)
- [ ] Verified containers are running (`make ps`)
- [ ] All 3 containers running:
  - [ ] `testcube-app` (PHP-FPM)
  - [ ] `testcube-web` (Nginx)
  - [ ] `testcube-db` (PostgreSQL)

**Verify with:**
```bash
make ps
# Should show 3 containers running
```

---

### Step 3: Laravel Configuration

- [ ] Accessed app container (`make app`)
- [ ] Copied Laravel `.env` file (`cp .env.example .env`)
- [ ] Generated application key (`php artisan key:generate`)
- [ ] Exited container (`exit`)
- [ ] Edited `src/.env` with database credentials:
  - [ ] `DB_CONNECTION=pgsql`
  - [ ] `DB_HOST=postgres`
  - [ ] `DB_PORT=5432`
  - [ ] `DB_DATABASE` matches root `.env` `POSTGRES_DB`
  - [ ] `DB_USERNAME` matches root `.env` `POSTGRES_USER`
  - [ ] `DB_PASSWORD` matches root `.env` `POSTGRES_PASSWORD`

**Verify `src/.env`:**
```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=secret_password
```

---

### Step 4: Database Setup

- [ ] Ran migrations and seeding (`make fresh`)
- [ ] No errors during migration
- [ ] Seeding completed successfully
- [ ] Database populated with:
  - [ ] 57 users (2 admins, 5 editors, 50 authors)
  - [ ] ~250-500 posts
  - [ ] ~1,250-25,000 comments
  - [ ] 4 roles
  - [ ] 12 permissions

**Verify with:**
```bash
make app
php artisan tinker
>>> \App\Models\User::count()
# Should return 57
>>> \App\Models\Post::count()
# Should return ~250-500
>>> \App\Models\Comment::count()
# Should return ~1,250-25,000
>>> exit
exit
```

---

### Step 5: Application Access

- [ ] Application accessible at [http://localhost:85](http://localhost:85)
- [ ] API accessible at [http://localhost:85/api](http://localhost:85/api)
- [ ] No 500 errors on homepage
- [ ] Laravel welcome page displays

**Verify with:**
```bash
curl http://localhost:85
# Should return HTML
```

---

## 🧪 Testing Checklist

### Run Tests

- [ ] Ran all tests (`make test`)
- [ ] All tests passing (62+ tests)
- [ ] No failures or errors
- [ ] Test output shows:
  - [ ] UserRegistrationTest (23 tests)
  - [ ] UserAuthenticationTest (21 tests)
  - [ ] EmailVerificationTest (18 tests)
  - [ ] Other feature tests

**Verify with:**
```bash
make test
# Expected: Tests: 62 passed
```

---

## 🔌 API Testing Checklist

### Test Authentication

- [ ] **Register** - Create new user
  ```bash
  curl -X POST http://localhost:85/api/register \
    -H "Content-Type: application/json" \
    -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'
  ```
  - [ ] Returns 201 status
  - [ ] Returns user object
  - [ ] Returns access token

- [ ] **Login** - Login with seeded user
  ```bash
  curl -X POST http://localhost:85/api/login \
    -H "Content-Type: application/json" \
    -d '{"email":"admin1@example.com","password":"password"}'
  ```
  - [ ] Returns 200 status
  - [ ] Returns user object
  - [ ] Returns access token

- [ ] **Get User** - Get authenticated user (use token from login)
  ```bash
  curl http://localhost:85/api/user \
    -H "Authorization: Bearer {your_token}"
  ```
  - [ ] Returns 200 status
  - [ ] Returns user with roles and permissions

---

### Test Public Endpoints

- [ ] **List Posts**
  ```bash
  curl http://localhost:85/api/posts | jq
  ```
  - [ ] Returns 200 status
  - [ ] Returns paginated posts
  - [ ] Shows author info
  - [ ] Shows comments count

- [ ] **Get Single Post**
  ```bash
  curl http://localhost:85/api/posts/1 | jq
  ```
  - [ ] Returns 200 status
  - [ ] Returns post with details

- [ ] **Search Posts**
  ```bash
  curl "http://localhost:85/api/posts/search?q=AI" | jq
  ```
  - [ ] Returns 200 status
  - [ ] Returns search results

---

### Test Statistics Endpoints

- [ ] **Post Statistics**
  ```bash
  curl http://localhost:85/api/stats/posts | jq
  ```
  - [ ] Returns 200 status
  - [ ] Shows total posts
  - [ ] Shows posts by status
  - [ ] Shows top commented posts

- [ ] **Comment Statistics**
  ```bash
  curl http://localhost:85/api/stats/comments | jq
  ```
  - [ ] Returns 200 status
  - [ ] Shows total comments
  - [ ] Shows activity by hour
  - [ ] Shows activity by weekday

- [ ] **User Statistics**
  ```bash
  curl http://localhost:85/api/stats/users | jq
  ```
  - [ ] Returns 200 status
  - [ ] Shows total users
  - [ ] Shows users by role
  - [ ] Shows top authors

---

### Test Roles Endpoint

- [ ] **Get All Roles**
  ```bash
  curl http://localhost:85/api/meta/roles | jq
  ```
  - [ ] Returns 200 status
  - [ ] Shows 4 roles
  - [ ] Shows permissions for each role

---

## 🎨 Code Quality Checklist

### PSR-12 Compliance

- [ ] Checked PSR-12 compliance
  ```bash
  docker compose exec app ./vendor/bin/pint --test
  ```
  - [ ] All files pass PSR-12 checks
  - [ ] No violations reported

---

## ⚡ Performance Checklist

### Caching

- [ ] Cache is working (check response times)
- [ ] Statistics endpoints are cached (fast response on 2nd call)
- [ ] Posts list is cached
- [ ] Single post is cached
- [ ] Roles metadata is cached

**Verify with:**
```bash
# First call (slower)
time curl http://localhost:85/api/stats/posts > /dev/null

# Second call (faster - cached)
time curl http://localhost:85/api/stats/posts > /dev/null
```

---

## 🔐 Security Checklist

### Authentication & Authorization

- [ ] Cannot access protected endpoints without token
- [ ] Token required for creating posts
- [ ] Token required for creating comments
- [ ] Authors can only edit own posts
- [ ] Authors can only edit own comments
- [ ] Admins can edit all posts
- [ ] Editors can edit all posts

**Verify with:**
```bash
# Try to create post without token (should fail)
curl -X POST http://localhost:85/api/posts \
  -H "Content-Type: application/json" \
  -d '{"title":"Test","body":"Test","status":"published"}'
# Expected: 401 Unauthorized
```

---

### Rate Limiting

- [ ] Public endpoints have rate limiting
- [ ] Rate limit is 60 requests/minute
- [ ] Rate limit headers present in response

**Verify with:**
```bash
curl -I http://localhost:85/api/posts
# Check for X-RateLimit-Limit and X-RateLimit-Remaining headers
```

---

## 📚 Documentation Checklist

### Documentation Files

- [ ] README.md exists and is complete
- [ ] QUICK_START.md exists
- [ ] DOCKER_SETUP.md exists
- [ ] API_REFERENCE.md exists
- [ ] AUTHENTICATION_TESTS_DOCUMENTATION.md exists
- [ ] DATABASE_SEEDING_GUIDE.md exists
- [ ] PSR12_COMPLIANCE_REPORT.md exists
- [ ] CACHING_OPTIMIZATION_DETAILS.md exists
- [ ] PROJECT_SUMMARY.md exists
- [ ] DOCUMENTATION_INDEX.md exists

---

## 🐳 Docker Checklist

### Container Health

- [ ] All containers running (`make ps`)
- [ ] No container restarts
- [ ] Logs show no errors (`make logs`)
- [ ] Can access app container (`make app`)
- [ ] Can access web container (`make web`)
- [ ] Database is accessible

**Verify with:**
```bash
make ps
# All containers should show "Up"

make logs
# No error messages
```

---

### Docker Commands

- [ ] `make up` works
- [ ] `make stop` works
- [ ] `make restart` works
- [ ] `make fresh` works
- [ ] `make test` works
- [ ] `make app` works
- [ ] `make logs-watch` works

---

## 🎯 Final Verification

### Complete System Check

- [ ] ✅ Docker containers running
- [ ] ✅ Database connected
- [ ] ✅ Database seeded
- [ ] ✅ Application accessible
- [ ] ✅ API endpoints working
- [ ] ✅ Tests passing
- [ ] ✅ Authentication working
- [ ] ✅ Authorization working
- [ ] ✅ Caching working
- [ ] ✅ Rate limiting working
- [ ] ✅ PSR-12 compliant
- [ ] ✅ Documentation complete

---

## 🎉 Success Criteria

Your setup is complete when:

1. ✅ All 3 Docker containers are running
2. ✅ Database has 57 users, ~250-500 posts, ~1,250-25,000 comments
3. ✅ All 62+ tests pass
4. ✅ Can login with `admin1@example.com` / `password`
5. ✅ Can access public endpoints without authentication
6. ✅ Can create posts with authentication
7. ✅ Statistics endpoints return data
8. ✅ Caching improves response times
9. ✅ PSR-12 compliance check passes
10. ✅ No errors in logs

---

## 🆘 Troubleshooting

If any checklist item fails, see:

- **Docker issues** → [DOCKER_SETUP.md](DOCKER_SETUP.md) → Troubleshooting
- **Database issues** → [README.md](README.md) → Troubleshooting
- **API issues** → [API_REFERENCE.md](API_REFERENCE.md)
- **Test failures** → [AUTHENTICATION_TESTS_DOCUMENTATION.md](AUTHENTICATION_TESTS_DOCUMENTATION.md)

---

## 📞 Quick Help

| Issue | Solution |
|-------|----------|
| Containers won't start | `make logs` to check errors |
| Database connection error | Check `src/.env` matches root `.env` |
| Tests failing | Run `make fresh` to reset database |
| Permission errors | `docker compose exec app chmod -R 777 storage bootstrap/cache` |
| Port 85 in use | Edit `docker-compose.yml` to change port |
| Complete reset needed | `make destroy && make build && make up && make fresh` |

---

**Congratulations! Your Laravel Blog API is ready!** 🎉🚀

