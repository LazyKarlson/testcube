# Docker Setup Guide

## 🐳 Docker Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Docker Network: testcube                 │
│                                                              │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐  │
│  │   Nginx      │    │   PHP-FPM    │    │  PostgreSQL  │  │
│  │   (web)      │◄───┤   (app)      │◄───┤   (postgres) │  │
│  │              │    │              │    │              │  │
│  │  Port: 85    │    │  Laravel 12  │    │  Port: 5432  │  │
│  └──────────────┘    └──────────────┘    └──────────────┘  │
│         │                    │                    │         │
│         │                    │                    │         │
│         ▼                    ▼                    ▼         │
│  localhost:85         /var/www/html      Database Storage  │
└─────────────────────────────────────────────────────────────┘
```

---

## 📦 Container Details

### 1. **app** (PHP-FPM)

**Image**: `php:8.2-fpm` (custom build)

**Purpose**: Runs Laravel application

**Installed:**
- PHP 8.2 with extensions (pdo_pgsql, mbstring, zip, etc.)
- Composer 2.3
- Node.js LTS
- Laravel Pint
- PHPUnit

**Volume**: `./src:/var/www/html`

**Access**: `make app` or `docker compose exec app bash`

---

### 2. **web** (Nginx)

**Image**: `nginx:stable-alpine`

**Purpose**: Web server (reverse proxy to PHP-FPM)

**Port**: `85:80` (host:container)

**Config**: `./docker/nginx/default.conf`

**Volume**: `./src:/var/www/html`

**Access**: `make web` or `docker compose exec web bash`

---

### 3. **postgres** (PostgreSQL)

**Image**: `postgres:15`

**Purpose**: Database server

**Port**: `5432:5432` (host:container)

**Data**: `./docker/postgres/data:/var/lib/postgres/data`

**Environment Variables** (from `.env`):
- `POSTGRES_DB`
- `POSTGRES_USER`
- `POSTGRES_PASSWORD`

**Access**: `docker compose exec postgres psql -U <username> -d <database>`

---

## 🔧 Configuration Files

### Root `.env`

Database credentials for Docker containers:

```env
POSTGRES_DB=laravel_db
POSTGRES_USER=laravel_user
POSTGRES_PASSWORD=secret_password
```

### `src/.env`

Laravel application configuration (must match root `.env`):

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:85

DB_CONNECTION=pgsql
DB_HOST=postgres              # Container name
DB_PORT=5432
DB_DATABASE=laravel_db        # Same as POSTGRES_DB
DB_USERNAME=laravel_user      # Same as POSTGRES_USER
DB_PASSWORD=secret_password   # Same as POSTGRES_PASSWORD

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

---

## 🚀 Docker Commands

### Container Lifecycle

```bash
# Build containers
make build
docker compose build

# Start containers
make up
docker compose up -d

# Stop containers
make stop
docker compose stop

# Restart containers
make restart

# Remove containers
make down
docker compose down

# Remove containers + volumes
make down-v
docker compose down --volumes

# Complete cleanup (images + volumes)
make destroy
docker compose down --rmi all --volumes --remove-orphans
```

---

### Container Access

```bash
# Access app container (PHP)
make app
docker compose exec app bash

# Access web container (Nginx)
make web
docker compose exec web bash

# Access database
docker compose exec postgres psql -U laravel_user -d laravel_db
```

---

### Logs

```bash
# View all logs
make logs
docker compose logs

# Follow logs (live)
make logs-watch
docker compose logs --follow

# App container logs
make log-app
make log-app-watch

# Web container logs
make log-web
make log-web-watch

# Database logs
make log-db
make log-db-watch
```

---

### Container Status

```bash
# List running containers
make ps
docker compose ps

# View container details
docker compose ps -a

# Check resource usage
docker stats
```

---

## 🗄️ Database Management

### Migrations

```bash
# Run migrations
make migrate
docker compose exec app php artisan migrate

# Fresh migration (drop all tables)
docker compose exec app php artisan migrate:fresh

# Fresh migration + seed
make fresh
docker compose exec app php artisan migrate:fresh --seed

# Rollback last migration
docker compose exec app php artisan migrate:rollback

# Rollback all migrations
docker compose exec app php artisan migrate:reset
```

---

### Seeding

```bash
# Seed database
make seed
docker compose exec app php artisan db:seed

# Seed specific seeder
docker compose exec app php artisan db:seed --class=UsersSeeder
docker compose exec app php artisan db:seed --class=PostsAndCommentsSeeder
```

---

### Database Access

```bash
# Access PostgreSQL CLI
docker compose exec postgres psql -U laravel_user -d laravel_db

# Inside psql:
\dt              # List tables
\d users         # Describe users table
SELECT * FROM users LIMIT 5;
\q               # Quit
```

---

## 🧪 Testing in Docker

### Run Tests

```bash
# Run all tests
make test
docker compose exec app php artisan test

# Run specific test file
docker compose exec app php artisan test tests/Feature/UserRegistrationTest.php

# Run with filter
docker compose exec app php artisan test --filter=UserAuthentication

# Run with coverage (if configured)
docker compose exec app php artisan test --coverage
```

---

### Code Quality

```bash
# Check PSR-12 compliance
docker compose exec app ./vendor/bin/pint --test

# Auto-fix PSR-12 violations
docker compose exec app ./vendor/bin/pint

# Clear compiled files
docker compose exec app php artisan clear-compiled

# Optimize autoloader
docker compose exec app composer dump-autoload -o
```

---

## 🔄 Development Workflow

### Typical Development Flow

```bash
# 1. Start containers
make up

# 2. Access app container
make app

# 3. Inside container: Install dependencies
composer install
npm install

# 4. Run migrations
php artisan migrate

# 5. Seed database
php artisan db:seed

# 6. Run tests
php artisan test

# 7. Exit container
exit

# 8. View logs
make logs-watch
```

---

### Making Changes

```bash
# After code changes, clear cache
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear

# Or use make command
make cache-clear

# Restart containers if needed
make restart
```

---

## 🛠️ Troubleshooting

### Container Won't Start

```bash
# Check logs
make logs

# Check specific container
make log-app
make log-web
make log-db

# Rebuild containers
make down
make build
make up
```

---

### Database Connection Issues

```bash
# 1. Verify PostgreSQL is running
make ps

# 2. Check database logs
make log-db

# 3. Verify credentials
cat .env
cat src/.env

# 4. Test connection from app container
make app
php artisan tinker
DB::connection()->getPdo();
```

---

### Permission Issues

```bash
# Fix storage permissions
docker compose exec app chmod -R 777 storage bootstrap/cache

# Fix ownership (if needed)
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

---

### Port Conflicts

If port 85 is already in use, edit `docker-compose.yml`:

```yaml
web:
  ports:
    - "8080:80"  # Change 85 to 8080 or any available port
```

Then restart:
```bash
make restart
```

---

### Complete Reset

```bash
# Nuclear option: destroy everything and start fresh
make destroy
make build
make up

# Configure Laravel
make app
cp .env.example .env
php artisan key:generate
exit

# Edit src/.env with database credentials

# Run migrations and seed
make fresh
```

---

## 📊 Resource Usage

### Check Container Resources

```bash
# View resource usage
docker stats

# View disk usage
docker system df

# Clean up unused resources
docker system prune
docker volume prune
docker image prune
```

---

## 🔐 Security Notes

### Production Considerations

1. **Change default credentials** in `.env`
2. **Use strong passwords** for database
3. **Set `APP_DEBUG=false`** in production
4. **Use HTTPS** (configure SSL in Nginx)
5. **Restrict database port** (don't expose 5432 publicly)
6. **Use environment-specific `.env`** files
7. **Enable Laravel's security features** (CSRF, etc.)

---

## 📚 Additional Resources

- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [Laravel Docker Documentation](https://laravel.com/docs/deployment#docker)
- [PostgreSQL Docker Hub](https://hub.docker.com/_/postgres)
- [Nginx Docker Hub](https://hub.docker.com/_/nginx)

---

## 🎯 Quick Reference

| Command | Description |
|---------|-------------|
| `make up` | Start containers |
| `make stop` | Stop containers |
| `make restart` | Restart containers |
| `make app` | Access app container |
| `make fresh` | Reset DB + seed |
| `make test` | Run tests |
| `make logs-watch` | View live logs |
| `make ps` | Container status |
| `make destroy` | Complete cleanup |

---

**Happy Dockering!** 🐳

