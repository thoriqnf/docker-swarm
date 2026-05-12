# 🐳 Laravel Todo CRUD — Docker Swarm Demo Plan

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

# 📅 DAY 1: Foundation & Cluster Logic

---

## Phase 1 — Laravel App + Docker Compose
> **Goal:** Fresh Laravel 12 app running locally with full Docker Compose stack

## Phase 2 — Docker Swarm: Basic Init
> **Goal:** Get Swarm initialized and first stack deployed — understand nodes, services, tasks

## Phase 3 — Orchestration & Self-Healing
> **Goal:** Demonstrate Swarm's internal cluster logic — promote/demote, quorum, and manual recovery

---

# 📅 DAY 2: Production Operations

---

## Phase 4 — Scaling, Rolling Updates & Rollbacks
> **Goal:** Manage dynamic capacity and handle software updates with zero downtime

## Phase 5 — Production Hardening (Optimization)
> **Goal:** Add resource limits and advanced deployment logic (start-first, failure-rollback)

## Phase 6 — Secrets Management
> **Goal:** Move all plaintext environment variables to secure Docker Secrets

## Phase 7 — CI/CD & Observability
> **Goal:** Automate deployments with GitHub Actions and monitor with Portainer

---

# 📋 Summary Timeline

| Day | Phase | Focus | Key Demo |
|---|---|---|---|
| Day 1 | 1 | Laravel + Compose | Local development |
| Day 1 | 2 | Swarm Init | Cluster setup |
| Day 1 | 3 | Orchestration | Roles & Self-healing |
| Day 2 | 4 | Scale & Rollback | Capacity & Updates |
| Day 2 | 5 | Hardening | Resource limits |
| Day 2 | 6 | Secrets | Security |
| Day 2 | 7 | Automation/Monitor | CI/CD & Portainer |
