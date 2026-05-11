# 🐳 Phase 1: Local Development (Docker Compose)

The goal of Phase 1 is to ensure the Laravel application and its infrastructure (MySQL, Redis, Nginx, Horizon) are working perfectly in a containerized environment before we move to Swarm.

## 🚀 Getting Started

```bash
# 1. Start the stack
# We've already built and fixed the images, so just start it:
docker compose up -d

# 2. Seed the data (MANDATORY for first-time setup)
# This creates the demo user and sample todos.
docker compose exec app php artisan migrate:fresh --seed

# 3. Access the App
# Open: http://localhost
```

## 🔑 Login Credentials

Use these credentials to access the dashboard after seeding:

- **Email**: `demo@example.com`
- **Password**: `password`

---

## 🎓 Phase 1 Walkthrough (How to Test)

To verify that the Docker stack is fully operational, follow these steps:

### 1. Database Connectivity (MySQL)
- **Action**: Log in and create a new Todo item.
- **Verification**: If the Todo appears in the list and persists after a page refresh, the **Laravel ↔ MySQL** connection is stable.

### 2. Queue & Background Jobs (Redis + Horizon)
- **Action**: Go to [http://localhost/horizon](http://localhost/horizon). 
- **Action**: Create a Todo in the main app.
- **Verification**: Check the "Completed Jobs" tab in Horizon. You should see a `SendTodoCreatedNotification` job. This proves **Redis** is working as a queue broker and **Horizon** is successfully consuming jobs.

### 3. Health & Environment (Centralized)
- **Action**: Visit [http://localhost/health](http://localhost/health).
- **Verification**: You should see a JSON response showing `ok` for both DB and Redis. Note the `hostname` field—in Phase 1, this will be the container ID of the app. In Phase 2 (Swarm), this will change as you load balance across nodes.

### 4. Cache Performance
- **Action**: The Todo list is cached for 60 seconds.
- **Verification**: Try creating a Todo and then check the Redis logs (`docker compose logs redis`). You'll see the activity.

---

## Services

| Service   | URL / Port            | Description            |
|-----------|-----------------------|------------------------|
| App       | http://localhost      | Laravel app via Nginx  |
| Horizon   | http://localhost/horizon | Queue dashboard     |
| Health    | http://localhost/health  | Health check JSON   |
| MySQL     | localhost:3306        | Database               |
| Redis     | localhost:6379        | Cache & queue broker   |

## Makefile Commands

```bash
make up          # Start all services
make down        # Stop all services
make build       # Build app image
make rebuild     # Force rebuild (no cache)
make shell       # SSH into app container
make migrate     # Run migrations
make seed        # Seed demo data
make fresh       # Fresh migrate + seed (⚠ destroys data)
make logs        # Follow all logs
make logs-app    # App logs only
make logs-horizon # Horizon logs
make health      # Check /health endpoint
make tinker      # Laravel Tinker REPL
make image       # Build Docker Hub image
make push        # Build + push to Docker Hub
```

## Phase Progression

| Phase | File | Description |
|-------|------|-------------|
| 1 | `docker-compose.yml` | Local dev — full stack |
| 2 | `docker-compose.swarm.yml` | Basic Swarm stack |
| 3 | `docker-compose.secrets.yml` | Swarm with Docker Secrets |
| 4 | Manual CLI | Scale, quorum, promote/demote |
| 5 | `docker-compose.prod.yml` | Production + rollback |
| 6 | `.github/workflows/deploy.yml` | CI/CD pipeline |
| 7 | `monitoring-stack.yml` | Health + Portainer |

## Architecture

```
nginx:80 → app:9000 (php-fpm)
              ↓
          mysql:3306
          redis:6379
              ↑
horizon (queue worker)
```

## Key Demo Features

- **`/health`** — Returns `{ status, db, redis, hostname }` — hostname shows which Swarm node served the request
- **Horizon** — Visual queue dashboard at `/horizon`
- **Queue jobs** — Every todo creation dispatches a background job (visible in Horizon)
- **Soft deletes** — Todos are soft-deleted (restorable)
- **Overdue highlighting** — Overdue todos shown in red
