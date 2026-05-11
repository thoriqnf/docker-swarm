# 🐳 Phase 2: Swarm Initialization (Raw CLI)

In Phase 2, we transition from a single-machine Compose setup to a **Docker Swarm Cluster**. We will initialize the cluster manually and deploy our first "Stack".

## 🚀 1. Initialize the Swarm

On your primary node (Manager), run:

```bash
docker swarm init
```

### Note for Multi-Node
If you have multiple nodes, run the `join` command provided by the output above on your worker nodes. You can always get the join token again using:
```bash
docker swarm join-token worker
```

## 📦 2. Deploy the Stack

Unlike `docker compose up`, Swarm uses `stack deploy`. We use the `docker-compose.swarm.yml` file which contains Swarm-specific configurations like `replicas` and `placement constraints`.

```bash
# Deploy the stack named 'todo'
docker stack deploy -c docker-compose.swarm.yml todo
```

## 🔍 3. Inspecting the Cluster

This is where the live demo gets interesting. Use these commands to see how Swarm manages your services:

```bash
# List all services in the stack
docker stack services todo

# List all tasks (containers) and see which node they are running on
docker stack ps todo

# List all nodes in the cluster
docker node ls
```

## 🛠️ Summary of Raw Commands

| Action | Command |
|--------|---------|
| Init Swarm | `docker swarm init` |
| Deploy Stack | `docker stack deploy -c docker-compose.swarm.yml todo` |
| List Services | `docker stack services todo` |
| List Tasks | `docker stack ps todo` |
| List Nodes | `docker node ls` |
| Remove Stack | `docker stack rm todo` |

---

> **Next Step**: Move to branch **`phase-3-raw`** to handle Secrets manually.
