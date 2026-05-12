# 🐳 Phase 7: CI/CD & Observability

In the final phase, we automate the boring stuff and give ourselves a window into the cluster's soul.

## 🚀 1. Automation with GitHub Actions

We implement a **Push-to-Deploy** pipeline. Every time you push to the `main` branch:
1.  **GitHub** builds a new Docker image.
2.  **GitHub** pushes it to Docker Hub with a unique Git SHA tag.
3.  **GitHub** SSHs into your Swarm Manager and triggers a zero-downtime update.

The workflow is defined in `.github/workflows/deploy.yml`.

---

## 📊 2. Observability (Visual Dashboards)

We deploy a separate monitoring stack to see exactly what's happening in our cluster.

### Portainer: The Swarm UI
Portainer gives you a beautiful web interface to manage nodes, services, and containers.
```bash
docker stack deploy -c docker-compose.monitoring.yml monitor
```
*Access it at `http://<MANAGER_IP>:9000`*

### cAdvisor: The Metrics Engine
cAdvisor runs on every node (`global` mode) and collects real-time CPU and Memory usage for every container.

---

## 🏁 The End of the Journey

You now have a:
- ✅ Scalable Laravel application
- ✅ Self-healing 5-node Swarm cluster
- ✅ Hardened production stack
- ✅ Secure secret management
- ✅ Automated delivery pipeline
- ✅ Real-time monitoring UI

### 🛠️ Final Commands Summary

| Action | Command |
|--------|---------|
| Deploy App | `docker stack deploy -c docker-compose.prod.yml todo` |
| Deploy Monitor | `docker stack deploy -c docker-compose.monitoring.yml monitor` |
| View Logs | `docker service logs -f <service_name>` |
| Check Health | `docker inspect --format='{{json .State.Health}}' <container_id>` |

---

> **Congratulations!** You are now a Docker Swarm Master.
