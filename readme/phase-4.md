# 🐳 Phase 4: Scaling, Rolling Updates & Rollback

In Phase 4, we shift from managing nodes to managing **service capacity and updates**. We will learn how to scale application instances instantly and how to perform safe updates with easy rollbacks.

## 📈 1. Horizontal Scaling

One of Swarm's greatest strengths is how easily it scales your application to handle more traffic.

```bash
# 1. Scale the app service to 10 replicas
docker service scale todo_app=10

# 2. Watch Swarm distribute the new tasks across the cluster
watch docker service ps todo_app

# 3. Scale back down to 3
docker service scale todo_app=3
```

---

## 🚀 2. Manual Rolling Update

When you have a new version of your code, you want to deploy it without taking the site down.

```bash
# 1. Build and push a new version (v2)
docker build -t yourdockerhub/todo-app:v2 .
docker push yourdockerhub/todo-app:v2

# 2. Update the service to the new image
# Swarm will replace containers one-by-one
docker service update --image yourdockerhub/todo-app:v2 todo_app

# 3. Monitor the rollout
docker service ps todo_app
```

---

## ⏪ 3. Manual Rollback

If you discover a bug in the new version, you can revert to the previous stable version in one command.

```bash
# Roll back the last update
docker service rollback todo_app
```

---

## 🛠️ Summary of Phase 4 Commands

| Action | Command |
|--------|---------|
| Scale Service | `docker service scale <name>=<n>` |
| Update Image | `docker service update --image <img:tag> <name>` |
| Rollback Service | `docker service rollback <name>` |
| View History | `docker service ps <name>` |

---

> **Next Step**: Now that we can manually scale and update, let's automate the safety! In **Phase 5**, we will add **Resource Limits and Auto-Rollbacks** to our stack file.
