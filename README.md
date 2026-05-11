# 🐳 Laravel Todo — Docker Swarm Demo

A Laravel 12 Todo CRUD app built to demonstrate Docker and Docker Swarm deployment from scratch.

## Quick Start (Phase 1 — Docker Compose)

```bash
# 1. Copy and configure environment
cp .env.example .env   # already exists as .env

# 2. Build & start all services
docker compose up -d --build

# 3. Run migrations & seed demo data
docker compose exec app php artisan migrate --seed

# 4. Open the app
open http://localhost

# 5. Login with demo credentials
#    Email:    demo@example.com
#    Password: password
```

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
