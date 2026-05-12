# 🐳 Phase 4: Scaling, Rolling Updates & Rollback

In Phase 4, we shift from managing nodes to managing **service capacity and updates**. This is where Swarm shines as an orchestrator, allowing you to handle traffic spikes and software updates without dropping a single request.

## 📈 1. Horizontal Scaling

One of Swarm's greatest strengths is how easily it scales your application.

```bash
# 1. Scale the app service to 10 replicas
docker service scale todo_app=10

# 2. Watch the tasks distribute across all workers and managers
watch docker service ps todo_app

# 3. Scale back down to 3 when the 'traffic' subsides
docker service scale todo_app=3
```

### Why scale?
Scaling across multiple nodes ensures that even if one node fails entirely, the other 9 replicas on different nodes continue to serve traffic.

---

## 🚀 2. Manual Rolling Update

When you have a new version (v2) of your app, you want to deploy it safely. By default, Swarm uses a "Rolling Update" strategy.

```bash
# 1. Build and push your new version (v2)
docker build -t yourdockerhub/todo-app:v2 .
docker push yourdockerhub/todo-app:v2

# 2. Update the existing service to the new image
# Swarm will replace containers one-by-one
docker service update --image yourdockerhub/todo-app:v2 todo_app

# 3. Monitor the transition
docker service ps todo_app
```

*During the update, you can keep refreshing the browser or running a 'curl' loop. You should never see a 404 or 503 error.*

---

## ⏪ 3. Manual Rollback

Mistakes happen. If the new version (v2) has a bug, you can revert to the previous version (v1) in seconds.

```bash
# Revert the service to the state BEFORE the last update
docker service rollback todo_app

# Verify that the image is back to v1
docker service inspect --format '{{.Spec.TaskTemplate.ContainerSpec.Image}}' todo_app
```

---

## 🛠️ Summary of Raw Commands

| Action | Command |
|--------|---------|
| Scale Service | `docker service scale <name>=<n>` |
| Update Image | `docker service update --image <img:tag> <name>` |
| Rollback | `docker service rollback <name>` |
| View History | `docker service ps <name>` |

---

> [!TIP]
> This completes **Day 1**. You now have a functional, scalable, and self-healing cluster. Ready for Day 2? We'll focus on **Production Hardening and Security**.
