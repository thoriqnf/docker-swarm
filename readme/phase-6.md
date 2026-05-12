# 🐳 Phase 6 — Maximizing Swarm on a Single Machine

> **Goal:** Transition from learning/simulation to a production-hardened single-node setup using Docker Stacks and Resource Management.

While Swarm is famous for multi-node clusters, it provides immense value even on a single machine. This phase focuses on **Stability**, **Resource Isolation**, and **Zero-Downtime Updates** using the professional `docker stack` workflow.

---

## ⚙️ 1. Why Swarm on Single Node?

Even without high availability across multiple physical servers, Swarm solves these critical problems:
1.  **Auto-Healing**: If a container crashes, Swarm restarts it automatically.
2.  **Resource Limits**: Prevents a single service (e.g., a memory leak in the API) from crashing the entire server.
3.  **Zero-Downtime Updates**: Rolling updates allow you to deploy new versions without interrupting users.
4.  **Forward Compatibility**: When you're ready to add a second server, your configuration doesn't need to change.

---

## 📦 2. Docker Stack Workflow

In production, we don't use `docker service create`. We use **Docker Stacks**, which allow us to define our entire environment in a single YAML file.

### Step 1: Create the Stack File
We have created a `docker-stack.yml` in the root directory. It includes:
- **Resource Limits**: Hard caps on CPU and RAM.
- **Reservations**: Guaranteed resources for each service.
- **Update Config**: Instructions for `start-first` rolling updates.

### Step 2: Deploy the Stack
```bash
# Deploy the stack (replaces 'docker compose up')
docker stack deploy -c docker-stack.yml todo_app
```

### Step 3: Manage the Stack
```bash
# List all services in the stack
docker stack services todo_app

# View all running tasks (replicas)
docker stack ps todo_app

# Check resource usage real-time
docker stats
```

---

## 📊 3. Resource Planning (16 Core / 128GB Example)

Based on **Module 8**, a well-planned node looks like this:

| Component | CPU Limit | RAM Limit | Purpose |
| :--- | :--- | :--- | :--- |
| **Nginx** | 0.5c | 512MB | Reverse Proxy |
| **API** (6x) | 1.5c | 4GB | Main Application Logic |
| **Worker** (4x) | 1.0c | 4GB | Background Processing |
| **Postgres** | 4.0c | 16GB | Database (Stateful) |
| **Redis** | 1.0c | 8GB | Cache / Queue |

> [!TIP]
> Always leave **20-30% headroom** (buffer) for OS processes, spikes in traffic, and the "start-first" update phase where extra containers run temporarily.

---

## 🔄 4. Zero-Downtime Rolling Updates

Our stack is configured to update replicas two at a time, starting new containers before stopping old ones:

```yaml
update_config:
  parallelism: 2
  order: start-first
  failure_action: rollback
  delay: 10s
```

To update your app, simply change the image tag in `docker-stack.yml` and run:
```bash
docker stack deploy -c docker-stack.yml todo_app
```

---

## 🏷️ 5. Node Labels & Constraints

Even on a single node, labels help you organize your workload. For example, pinning a database to a node with high-speed SSDs:

```bash
# Add a label to your manager node
docker node update --label-add ssd=true manager1

# Verify label
docker node inspect manager1 --format '{{ .Spec.Labels }}'
```

The `docker-stack.yml` uses `placement.constraints` to ensure the database only runs on nodes labeled `ssd=true`.

---

## 📝 6. Key Takeaways
- **Reservations** are for scheduling (guaranteed resources).
- **Limits** are for enforcement (preventing resource hogs).
- **Replicas** on a single node are used to utilize all CPU cores, not for HA.
- **Docker Stack** is the industry-standard way to manage Swarm services.

---

### Next — Phase 7: CI/CD Pipeline
Now that the production stack is ready, we will automate the entire process with GitHub Actions.
