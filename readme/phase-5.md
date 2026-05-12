# 🐳 Phase 5: Production Hardening (Optimization)

In Phase 5, we transition from "it works" to "it's production-ready". We will optimize how our services use resources and how Swarm handles failures automatically.

## 🛡️ 1. Resource Limits

Without limits, a single buggy service could consume all the CPU or Memory on a node, crashing other services. We now define strict boundaries in `docker-compose.prod.yml`:

```yaml
resources:
  limits:
    cpus: '0.50'
    memory: 128M
```

## 🚀 2. Advanced Deployment Logic

We automate the safety checks that we performed manually in Phase 4.

### A. Failure Action: Rollback
If a new container fails to start or fails its health check, Swarm will **automatically revert** to the previous version.

### B. Start-First Order
Instead of stopping the old version before starting the new one (causing downtime), we start the new version **first**, wait for it to be healthy, and only then stop the old one.

```yaml
update_config:
  parallelism: 1
  delay: 15s
  failure_action: rollback
  monitor: 30s
  order: start-first
```

---

## 🚀 3. Deploying the Hardened Stack

```bash
# 1. Deploy using the production stack file
docker stack deploy -c docker-compose.prod.yml todo

# 2. Inspect the resource limits on a running task
docker inspect <container_id> --format='{{json .HostConfig.NanoCpus}}'
```

---

## 🛠️ Summary of Phase 5 Commands

| Action | Command |
|--------|---------|
| Deploy Prod Stack | `docker stack deploy -c docker-compose.prod.yml todo` |
| Inspect Service | `docker service inspect --pretty <name>` |
| Watch Updates | `watch docker service ps <name>` |

---

> **Next Step**: Our stack is robust, but our passwords are still in plaintext! In **Phase 6**, we will solve this using **Docker Secrets**.
