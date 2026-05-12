# 🐳 Phase 6: Secrets Management

In Phase 6, we secure our cluster by removing all plaintext passwords from our configuration files and using **Docker Secrets**. 

## 🔐 1. Why Secrets?

In previous phases, passwords were visible in `docker-compose.prod.yml` or in environment variables. 
- **Docker Secrets** are encrypted at rest on the manager nodes.
- They are only transmitted to worker nodes that actually need them.
- They are never written to disk on the worker nodes (mounted in-memory at `/run/secrets/`).

## 🛠️ 2. Creating Secrets

Run these commands on your Manager node:

```bash
# Create the secrets
echo "todo_db" | docker secret create db_name -
echo "todo_user" | docker secret create db_user -
echo "secret_password" | docker secret create db_password -
echo "root_secret" | docker secret create db_root_password -
echo "redis_secret" | docker secret create redis_password -
echo "base64:your_app_key" | docker secret create app_key -

# Verify
docker secret ls
```

## 🏗️ 3. Using Secrets in the Stack

In `docker-compose.prod.yml`, we now reference these secrets as `external: true`. Laravel and MySQL are configured to read their credentials from the files in `/run/secrets/`.

```bash
# Deploy the updated stack
docker stack deploy -c docker-compose.prod.yml todo
```

---

## 🛠️ Summary of Phase 6 Commands

| Action | Command |
|--------|---------|
| Create Secret | `echo "val" | docker secret create <name> -` |
| List Secrets | `docker secret ls` |
| Remove Secret | `docker secret rm <name>` |
| Inspect Secret | `docker secret inspect <name>` |

---

> **Next Step**: Our cluster is secure and hardened. The final step is to automate the deployment and add visibility. In **Phase 7**, we'll set up **CI/CD and Observability**.
