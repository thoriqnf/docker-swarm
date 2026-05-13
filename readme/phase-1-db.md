# 🐘 Phase 1-DB: External Database Integration

In this sub-phase, we transition from a local MySQL container to an **external PostgreSQL database**. This mimics real-world scenarios where databases are managed separately (e.g., RDS, managed DBs, or a shared corporate DB).

## 📋 1. Configuration Changes

The stack was modified to support PostgreSQL and external connections.

### Environment (`.env`)
The following variables were updated in the root `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=100.66.190.92
DB_PORT=5432
DB_DATABASE=todo_db
DB_USERNAME=postgres
DB_PASSWORD=changeme
```

### Docker Compose (`docker-compose.yml`)
- **Removed `mysql` service**: The local database container is no longer needed.
- **Removed `mysql_data` volume**: Persistent storage is now handled by the external provider.
- **Updated `app` & `horizon`**: Environment variables now source from the `.env` file or default to the external credentials.

## 🛠️ 2. PHP Image Updates

To support PostgreSQL, the `docker/php/Dockerfile` was updated to include the necessary drivers:

```dockerfile
# Added system dependencies
RUN apk add --no-cache postgresql-dev

# Added PHP extensions
RUN docker-php-ext-install pdo_pgsql
```

## 🚀 3. How to Start

Since the Dockerfile changed, you must rebuild the image:

```bash
# Rebuild the PHP image
docker compose build

# Start the application
docker compose up -d
```

## 🔑 4. Database Setup

Once the app is running, run migrations on the external database:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

---

> [!TIP]
> If you encounter "Connection Refused" errors, ensure that the external DB host (`100.66.190.92`) is reachable from your machine and allows traffic on port `5432`.
