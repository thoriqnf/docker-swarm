# 🐳 Phase 3: Secrets Management (Raw CLI)

In Phase 3, we replace all plaintext passwords and keys with **Docker Secrets**. This is the core of production security in Swarm.

## 🔐 1. Create Secrets Manually

In a live demo, we manually create the secrets to show how they are stored in the Manager's encrypted Raft log.

```bash
# 1. Database Secrets
echo "todo_db" | docker secret create db_name -
echo "todo_user" | docker secret create db_user -
echo "secret" | docker secret create db_password -
echo "rootsecret" | docker secret create db_root_password -

# 2. Redis Secrets
echo "redis_secret_pass" | docker secret create redis_password -

# 3. Laravel APP_KEY
echo "base64:aLILxFWKOHdf4/GyZLtT65Z42hdDIbsdbGuJgI8ejbg=" | docker secret create app_key -
```

### Verify Secrets
```bash
docker secret ls
```

## 🚀 2. Deploy the Secured Stack

We now deploy using `docker-compose.secrets.yml`, which references the secrets we just created as `external: true`.

```bash
docker stack deploy -c docker-compose.secrets.yml todo
```

## 🔍 3. Verify Security

Show the audience that the passwords are no longer visible in the service configuration:

```bash
# Inspect the environment variables — notice NO passwords/keys!
docker service inspect todo_app --format '{{json .Spec.TaskTemplate.ContainerSpec.Env}}'

# Verify the secrets are mounted as secure files
docker exec $(docker ps -q -f name=todo_app | head -n 1) ls /run/secrets/
```

### 🎯 Why did we do this?
- **Security**: In Phase 1, anyone with access to the Docker API could see your password by "inspecting" the container. Now, the passwords are **only** visible to the application process as temporary files in `/run/secrets/`.
- **In-Memory**: These secret files never touch the hard drive; they live in the container's RAM.
- **Separation of Concerns**: You can change a password by updating the Docker Secret without ever touching your source code or `.env` files.

## 🛠️ Summary of Raw Commands

| Action | Command |
|--------|---------|
| Create Secret | `echo "val" | docker secret create name -` |
| List Secrets | `docker secret ls` |
| Inspect Secret | `docker secret inspect name` |
| Remove Secret | `docker secret rm name` |

---

> **Next Step**: Move to branch **`phase-4`** to demonstrate Orchestration & Self-Healing.
