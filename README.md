# 🐳 Docker Swarm Hands-on Tutorial

This repository contains a multi-phase tutorial for mastering Docker Swarm, starting from local development to production-grade orchestration and CI/CD.

## 🗺️ Roadmap

### Day 1: Foundation & Cluster Logic
*   **[Phase 1: Local Foundations](readme/phase-1.md)** - Raw Docker Compose setup.
*   **[Phase 1-DB: External DB Integration](readme/phase-1-db.md)** - Connecting to external PostgreSQL.
*   **Phase 2: Swarm Initialization** - Converting Compose to Swarm.
*   **Phase 3: Orchestration & Self-Healing** - Roles, quorum, and recovery.
*   **Phase 4: Scaling & Rollbacks** - Zero-downtime updates.

### Day 2: Production Operations
*   **Phase 5: Production Hardening** - Resource limits and health checks.
*   **Phase 6: Secrets Management** - Secure environment variables.
*   **Phase 7: CI/CD & Observability** - GitHub Actions and Portainer.

## 🚀 Quick Start (Phase 1)

```bash
# Copy env and build
cp .env.example .env
docker compose build
docker compose up -d

# Run migrations
docker compose exec app php artisan migrate --seed
```

For detailed instructions on each phase, follow the links above.
