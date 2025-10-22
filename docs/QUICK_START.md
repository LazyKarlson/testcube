# Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Clone & Configure (1 min)

```bash
# Clone repository
git clone <repository-url>
cd testcube

# Setup environment
cp .env.example .env
nano .env  # Edit with your database credentials
```

**Example `.env`:**
```env
POSTGRES_DB=laravel_db
POSTGRES_USER=laravel_user
POSTGRES_PASSWORD=secret_password
```

---

### Step 2: Start Docker (1 min)

```bash
# Build and start containers
make build
make up

# Verify containers are running
make ps
```

---

### Step 3: Configure Laravel (1 min)

```bash
# Access app container
make app

# Inside container:
cp .env.example .env
php artisan key:generate
exit
```

**Edit `src/.env`** (must match root `.env`):
```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=secret_password
```

---

### Step 4: Setup Database (1 min)

```bash
# Run migrations and seed with sample data
make fresh
```

**This creates:**
- ✅ 57 users (2 admins, 5 editors, 50 authors)
- ✅ ~250-500 posts with realistic content
- ✅ ~1,250-25,000 comments

**Sample credentials:**
```
admin1@example.com / password
editor1@example.com / password
author1@example.com / password
```

---

### Step 5: Test the API (1 min)

```bash
# Test login
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin1@example.com","password":"password"}' | jq

# Get posts
curl http://localhost:85/api/posts | jq

# Get statistics
curl http://localhost:85/api/stats/posts | jq
```

---

## ✅ You're Ready!

**Application**: [http://localhost:85](http://localhost:85)

**API Base**: [http://localhost:85/api](http://localhost:85/api)

---

## 🧪 Run Tests

```bash
# Run all tests
make test

# Expected: 62 tests passed ✅
```

---

## 📚 Next Steps

1. **Explore API Endpoints** - See `README.md` for complete API documentation
2. **Run Tests** - `make test` to verify everything works
3. **Check Documentation** - Review `AUTHENTICATION_TESTS_DOCUMENTATION.md`
4. **Customize** - Start building your features!

---

## 🆘 Need Help?

**Common Issues:**

1. **Database connection error**
   - Check `src/.env` matches root `.env`
   - Run `make log-db` to check database logs

2. **Permission errors**
   - Run `docker compose exec app chmod -R 777 storage bootstrap/cache`

3. **Reset everything**
   - Run `make destroy && make build && make up && make fresh`

---

## 🎯 Essential Commands

```bash
make up          # Start containers
make stop        # Stop containers
make fresh       # Reset database + seed
make test        # Run tests
make app         # Access app container
make logs-watch  # View live logs
```

---

**Happy coding!** 🚀

