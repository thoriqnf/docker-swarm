# 🐳 Laravel Todo CRUD — Docker Swarm Demo Plan

## Tech Stack Decision

| Layer | Choice | Reason |
|---|---|---|
| Laravel | **v12** | Latest stable, most adopted |
| PHP | **8.3-fpm** | LTS, best performance |
| Frontend | **Breeze + Blade** | Simplest, no JS framework needed for demo |
| Web Server | **Nginx** | Most common in production |
| Database | **MySQL 8** | Industry standard |
| Cache/Queue | **Redis 7** | Required for Horizon |
| Queue Dashboard | **Laravel Horizon** | Native queue monitoring |
| Ingress | **Traefik v2** | Swarm-native, auto service discovery |
| Registry | **Docker Hub** | Free, most universal |
| Monitoring | **Portainer CE** | Visual Swarm management |
| CI/CD | **GitHub Actions** | Most widely used, free |

## Swarm Topology (5 Nodes)

```
┌─────────────────────────────────────────────────┐
│              Docker Swarm Cluster                │
│                                                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐      │
│  │ manager1 │  │ manager2 │  │ manager3 │      │
│  │ (leader) │  │ (manager)│  │ (manager)│      │
│  └──────────┘  └──────────┘  └──────────┘      │
│       ↑ Raft Quorum (need 2/3 alive)             │
│                                                  │
│  ┌──────────┐  ┌──────────┐                     │
│  │ worker1  │  │ worker2  │                     │
│  └──────────┘  └──────────┘                     │
│                                                  │
│  Quorum = (5/2)+1 = 3 → can lose 2 managers     │
└─────────────────────────────────────────────────┘
```

---

# 📅 DAY 1

---

## Phase 1 — Laravel App + Docker Compose

> **Goal:** Fresh Laravel 12 app running locally with full Docker Compose stack

### 1.1 Todo App Features (enough to stress Docker)

| Feature | Purpose in Demo |
|---|---|
| User Auth (Breeze) | Separate web + session traffic |
| Todo CRUD | Core app logic |
| Categories | Relational DB queries |
| Priority + Due Dates | Rich data model |
| Mark Complete | State mutation |
| Queue Job on Create | Worker service needed |
| Search/Filter | Redis cache layer needed |
| API endpoints (`/api/todos`) | Load test target |

### 1.2 Docker Services (Compose)

```
┌─────────────────────────────────────────┐
│         docker-compose.yml              │
│                                         │
│  nginx ─────→ app (php-fpm:8.3)        │
│                    ↓                    │
│               mysql:8.0                 │
│               redis:7                   │
│  horizon ───→ redis (queue consumer)   │
└─────────────────────────────────────────┘
```

### 1.3 File Structure

```
docker-swarm/
├── src/                          # Laravel 12 app
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── TodoController.php
│   │   │   └── CategoryController.php
│   │   ├── Jobs/
│   │   │   └── SendTodoCreatedNotification.php
│   │   └── Models/
│   │       ├── Todo.php
│   │       └── Category.php
│   └── ...
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   └── horizon/
│       └── Dockerfile
├── docker-compose.yml            # Phase 1 — local dev
├── docker-compose.swarm.yml      # Phase 2 — base swarm
├── docker-compose.secrets.yml    # Phase 3 — with secrets
├── docker-compose.prod.yml       # Phase 5 — production
├── .github/
│   └── workflows/
│       └── deploy.yml            # Phase 6 — CI/CD
└── scripts/
    ├── init-swarm.sh
    ├── deploy.sh
    └── rollback.sh
```

### 1.4 Dockerfile Strategy

```dockerfile
# Multi-stage build
# Stage 1: composer deps
FROM composer:2 AS vendor

# Stage 2: node assets
FROM node:20-alpine AS assets

# Stage 3: final PHP-FPM image
FROM php:8.3-fpm-alpine AS app
```

### 1.5 Key docker-compose.yml Services

```yaml
services:
  nginx:      # port 80
  app:        # php-fpm, depends_on: [mysql, redis]
  mysql:      # port 3306, named volume
  redis:      # port 6379
  horizon:    # queue worker, same image as app
```

### ✅ Phase 1 Deliverables
- [ ] `git clone` → `docker compose up` → app running at `localhost:80`
- [ ] Auth working (register/login)
- [ ] Full Todo CRUD
- [ ] Horizon dashboard at `/horizon`
- [ ] Queue job fires on todo creation

---

## Phase 2 — Docker Swarm: Basic Init

> **Goal:** Get Swarm initialized and first stack deployed — understand nodes, services, tasks

### 2.1 VM Setup Options

| Option | Tool | Cost |
|---|---|---|
| Local (Mac) | Multipass / Lima | Free |
| Cloud | DigitalOcean Droplets | ~$6/mo each |
| Cloud | Hetzner VPS | ~$4/mo each |

### 2.2 Init Sequence

```bash
# On manager1
docker swarm init --advertise-addr <MANAGER1_IP>

# On manager2, manager3 (join as manager)
docker swarm join --token <MANAGER_TOKEN> <MANAGER1_IP>:2377

# On worker1, worker2 (join as worker)
docker swarm join --token <WORKER_TOKEN> <MANAGER1_IP>:2377

# Verify
docker node ls
```

### 2.3 Stack File (Swarm)

Convert `docker-compose.yml` → `docker-compose.swarm.yml` with:
- `deploy.replicas`
- `deploy.placement.constraints`
- `deploy.restart_policy`
- Overlay networks (instead of bridge)
- Remove `build:` (use pre-built images)

### 2.4 Deploy First Stack

```bash
# Push image to Docker Hub first
docker build -t yourdockerhub/todo-app:latest .
docker push yourdockerhub/todo-app:latest

# Deploy stack
docker stack deploy -c docker-compose.swarm.yml todo
docker stack services todo
docker service ps todo_app
```

### ✅ Phase 2 Deliverables
- [ ] 5-node Swarm cluster running
- [ ] `docker node ls` shows all nodes
- [ ] Stack deployed successfully
- [ ] App accessible via manager IP

---

## Phase 3 — Docker Swarm with Secrets

> **Goal:** Replace all plaintext env vars with Docker Secrets — production-ready security

### 3.1 Why Secrets?

| Method | Risk |
|---|---|
| `.env` file | Exposed in image/compose file |
| `environment:` in compose | Visible in `docker inspect` |
| **Docker Secret** | Encrypted at rest, only in-memory |

### 3.2 Secrets to Create

```bash
# Create secrets
echo "todo_db_password_here" | docker secret create db_password -
echo "todo_db_name" | docker secret create db_name -
echo "your_app_key_here" | docker secret create app_key -
echo "redis_password_here" | docker secret create redis_password -

# List secrets
docker secret ls
```

### 3.3 Secret Pattern in PHP

```php
// config/database.php — read from /run/secrets/
'password' => file_exists('/run/secrets/db_password')
    ? trim(file_get_contents('/run/secrets/db_password'))
    : env('DB_PASSWORD'),
```

### 3.4 Stack File Secret Reference

```yaml
services:
  app:
    secrets:
      - db_password
      - app_key

secrets:
  db_password:
    external: true
  app_key:
    external: true
```

### ✅ Phase 3 Deliverables
- [ ] Zero plaintext passwords in any stack file
- [ ] `docker secret ls` shows all secrets
- [ ] App running correctly reading from `/run/secrets/`
- [ ] `docker inspect` shows no exposed passwords

---

## Phase 4 — Scaling, Quorum, Promote & Demote

> **Goal:** Demonstrate Swarm's self-healing, leader election, and horizontal scaling

### 4.1 Scaling Demo

```bash
# Scale app to 5 replicas across workers
docker service scale todo_app=5

# Watch tasks distribute
watch docker service ps todo_app

# Scale down
docker service scale todo_app=2
```

### 4.2 Quorum Demo (Raft Consensus)

```
With 3 managers: quorum = 2
- Kill 1 manager → cluster still works ✅
- Kill 2 managers → cluster frozen ❌ (split brain protection)
- Restore 1 manager → cluster recovers ✅
```

```bash
# Simulate manager failure (on manager3)
sudo systemctl stop docker

# Check leader election on manager1
docker node ls   # manager3 shows "Down", new leader elected

# Restore
sudo systemctl start docker
```

### 4.3 Promote & Demote

```bash
# Promote worker1 to manager
docker node promote worker1

# Demote manager3 to worker
docker node demote manager3

# Drain a node (move tasks away before maintenance)
docker node update --availability drain worker1

# Re-activate
docker node update --availability active worker1
```

### 4.4 Self-Healing Demo

```bash
# Kill a container manually — Swarm restarts it automatically
docker kill <container_id_on_worker>
docker service ps todo_app   # Watch "Failed" then "Running"
```

### ✅ Phase 4 Deliverables
- [ ] `docker service scale` working across nodes
- [ ] Quorum loss/recovery demonstrated
- [ ] Promote/demote nodes without downtime
- [ ] Self-healing shown after container kill

---

# 📅 DAY 2

---

## Phase 5 — Production Stacks & Rollback

> **Goal:** Separate configs per environment, deploy new versions with zero downtime, rollback on failure

### 5.1 Stack Environments

```
docker-compose.prod.yml     # production values
docker-compose.staging.yml  # staging override
```

### 5.2 Rolling Update Demo

```bash
# Build new version
docker build -t yourdockerhub/todo-app:v2 .
docker push yourdockerhub/todo-app:v2

# Rolling update (1 at a time, 10s between)
docker service update \
  --image yourdockerhub/todo-app:v2 \
  --update-parallelism 1 \
  --update-delay 10s \
  todo_app

# Watch rolling update
watch docker service ps todo_app
```

### 5.3 Rollback

```bash
# Rollback to previous image
docker service rollback todo_app

# Or pin to specific version
docker service update --image yourdockerhub/todo-app:v1 todo_app
```

### 5.4 Update Config

```yaml
# In stack file
deploy:
  update_config:
    parallelism: 1
    delay: 10s
    failure_action: rollback     # auto rollback on failure!
    monitor: 30s
  rollback_config:
    parallelism: 1
    delay: 5s
```

### ✅ Phase 5 Deliverables
- [ ] v1 → v2 rolling update with zero downtime (verify with `curl` loop)
- [ ] Manual rollback works
- [ ] `failure_action: rollback` auto-triggers on bad deploy
- [ ] Separate prod stack file

---

## Phase 6 — CI/CD with GitHub Actions

> **Goal:** Push to `main` → image built → pushed to Docker Hub → auto-deployed to Swarm

### 6.1 Workflow Overview

```
┌──────────┐    push     ┌──────────────────────────────────────┐
│  GitHub  │──────────→  │         GitHub Actions                │
│  main    │             │                                        │
└──────────┘             │  1. Checkout code                     │
                         │  2. Build multi-stage Docker image     │
                         │  3. Run tests (php artisan test)       │
                         │  4. Push to Docker Hub                 │
                         │  5. SSH into manager1                  │
                         │  6. docker service update              │
                         │  7. Health check — rollback if fails   │
                         └──────────────────────────────────────┘
```

### 6.2 GitHub Secrets Required

```
DOCKER_USERNAME
DOCKER_PASSWORD
SWARM_HOST          # manager1 IP
SWARM_SSH_KEY       # private key for SSH
SWARM_SSH_USER      # ubuntu / root
```

### 6.3 Workflow File Highlights

```yaml
# .github/workflows/deploy.yml
name: Deploy to Swarm
on:
  push:
    branches: [main]

jobs:
  build-and-deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Build & Push
        run: |
          docker build -t ${{ secrets.DOCKER_USERNAME }}/todo-app:${{ github.sha }} .
          docker push ${{ secrets.DOCKER_USERNAME }}/todo-app:${{ github.sha }}
      - name: Deploy to Swarm
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SWARM_HOST }}
          key: ${{ secrets.SWARM_SSH_KEY }}
          script: |
            docker service update \
              --image ${{ secrets.DOCKER_USERNAME }}/todo-app:${{ github.sha }} \
              --update-failure-action rollback \
              todo_app
```

### 6.4 Tagging Strategy

| Tag | Trigger |
|---|---|
| `latest` | merge to `main` |
| `v1.2.3` | git tag push |
| `sha-abc1234` | every commit (for rollback reference) |

### ✅ Phase 6 Deliverables
- [ ] Push to `main` triggers full pipeline
- [ ] Failed test blocks deploy
- [ ] Successful deploy updates Swarm service
- [ ] Bad deploy auto-rolls back
- [ ] Each commit has unique SHA-tagged image

---

## Phase 7 — Health Checks & Simple Monitoring

> **Goal:** Swarm knows when a container is unhealthy, auto-replaces it; basic visibility into cluster

### 7.1 Laravel Health Check Endpoint

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'db'     => DB::connection()->getPdo() ? 'ok' : 'fail',
        'redis'  => Redis::ping() === 'PONG' ? 'ok' : 'fail',
        'ts'     => now()->toIso8601String(),
    ]);
});
```

### 7.2 Dockerfile Health Check

```dockerfile
HEALTHCHECK --interval=30s \
            --timeout=5s \
            --start-period=30s \
            --retries=3 \
  CMD curl -f http://localhost/health || exit 1
```

### 7.3 Stack Health Check

```yaml
services:
  app:
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health"]
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 30s
```

> Swarm will mark task as unhealthy and reschedule automatically!

### 7.4 Monitoring Stack

```yaml
# monitoring-stack.yml
services:
  portainer:
    image: portainer/portainer-ce:latest
    ports: ["9000:9000"]
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
    deploy:
      placement:
        constraints: [node.role == manager]

  cadvisor:
    image: gcr.io/cadvisor/cadvisor:latest
    volumes:
      - /:/rootfs:ro
      - /var/run:/var/run:ro
      - /sys:/sys:ro
      - /var/lib/docker/:/var/lib/docker:ro
    deploy:
      mode: global    # runs on EVERY node
```

### 7.5 Log Aggregation (Simple)

```bash
# View all tasks logs for a service
docker service logs -f todo_app

# Filter by node
docker service logs --raw todo_app 2>&1 | grep ERROR
```

### ✅ Phase 7 Deliverables
- [ ] `/health` endpoint returns DB + Redis status
- [ ] Unhealthy container auto-replaced by Swarm
- [ ] Portainer UI showing all nodes and services
- [ ] cAdvisor collecting container metrics on every node
- [ ] `docker service logs` working for centralized log view

---

# 📋 Summary Timeline

| Day | Phase | Focus | Key Demo |
|---|---|---|---|
| Day 1 | 1 | Laravel + Docker Compose | `docker compose up` → full app |
| Day 1 | 2 | Swarm Init | `docker node ls` 5 nodes |
| Day 1 | 3 | Secrets | Zero plaintext passwords |
| Day 1 | 4 | Scale/Quorum | Kill manager, Swarm survives |
| Day 2 | 5 | Prod + Rollback | Zero-downtime deploy + rollback |
| Day 2 | 6 | CI/CD | Push code → auto live |
| Day 2 | 7 | Health + Monitor | Portainer, auto-heal |

---

> **Next step:** Approve this plan → I'll start building Phase 1 (Laravel app + Dockerfile + docker-compose.yml) in `/Users/thq/Documents/tlkm/docker-swarm/`
