# 🐳 Laravel Todo CRUD — Docker Swarm Demo Plan

## Tech Stack Decision

| Layer | Choice | Reason |
|---|---|---|
| Laravel | **v12** | Latest stable |
| PHP | **8.4-fpm** | LTS, best performance |
| Web Server | **Nginx** | Industry standard |
| Database | **MySQL 8** | Industry standard |
| Cache/Queue | **Redis 7** | Required for Horizon |
| Monitoring | **Portainer CE** | Visual Swarm management |
| CI/CD | **GitHub Actions** | Most widely used |

---

# 📅 DAY 1: Foundation & Cluster Logic

---

## Phase 1 — Laravel App + Docker Compose
> **Goal:** Fresh Laravel 12 app running locally with full Docker Compose stack

### 1.1 ✅ Deliverables
- [ ] `docker compose up` → app running at `localhost:80`
- [ ] Auth working (Breeze)
- [ ] Queue job fires on todo creation

---

## Phase 1-DB — External Database Migration
> **Goal:** Transition from local MySQL container to external PostgreSQL
- [ ] Connect to external host `100.66.190.92`
- [ ] Configure `pdo_pgsql` driver in Docker image
- [ ] Verify migrations on external DB

---

## Phase 2 — Docker Swarm: Basic Init
> **Goal:** Get Swarm initialized and first stack deployed

---

## Phase 3 — Orchestration & Self-Healing
> **Goal:** Demonstrate Swarm's internal cluster logic — roles, quorum, and manual recovery

### 3.1 Key Concepts
- **Promote/Demote**: Turning workers into managers.
- **Drain**: Safe node maintenance.
- **Self-Healing**: Manual container kills and auto-recovery.

---

## Phase 4 — Scaling, Rolling Updates & Rollbacks
> **Goal:** Manage dynamic capacity and handle software updates with zero downtime

### 4.1 Features
- **Horizontal Scaling**: `docker service scale todo_app=10`.
- **Rolling Update**: `docker service update --image v2`.
- **Manual Rollback**: `docker service rollback`.

---

# 📅 DAY 2: Production Operations

---

## Phase 5 — Production Hardening (Optimization)
> **Goal:** Add resource limits and advanced deployment logic

### 5.1 Optimization
- **Resource Limits**: CPU and Memory constraints.
- **Deployment Strategy**: `start-first` order and failure monitoring.

---

## Phase 6 — Secrets Management
> **Goal:** Move all plaintext environment variables to secure Docker Secrets

---

## Phase 7 — CI/CD & Observability
> **Goal:** Automate deployments with GitHub Actions and monitor with Portainer

---

# 📋 Summary Timeline

| Day | Phase | Focus | Key Demo |
|---|---|---|---|
| Day 1 | 1 | Laravel + Compose | Local development |
| Day 1 | 1-DB | External DB | Managed PostgreSQL |
| Day 1 | 2 | Swarm Init | Cluster setup |
| Day 1 | 3 | Orchestration | Roles & Self-healing |
| Day 1 | 4 | Scale & Rollback | Capacity & Updates |
| Day 2 | 5 | Hardening | Resource limits |
| Day 2 | 6 | Secrets | Security |
| Day 2 | 7 | Automation/Monitor | CI/CD & Portainer |
