# 🐳 Phase 2: Swarm Initialization (Raw CLI)

In Phase 2, we transition from a single-machine Compose setup to a **Docker Swarm Cluster**. We will initialize the cluster manually and deploy our first "Stack".

## 🚀 1. Initialize the Swarm

On your primary node (Manager), run:

```bash
docker swarm init
```

> [!TIP]
> If you get an error saying "This node is already part of a swarm", you can reset it by running:
> `docker swarm leave --force`

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

---

## 🧠 3. Understanding the "Cluster"

Since you are running on **Docker Desktop**, you currently have a **1-node cluster**. In a real-world scenario (like AWS or DigitalOcean), you would see multiple lines here.

### 1. The Manager vs. Worker Roles
- **Manager (Leader)**: The "brain." It keeps the desired state (e.g., "I want 2 apps running").
- **Worker**: The "muscle." It just runs the containers. 
- *Note*: In single-node setups, your Manager also acts as a Worker.

### 2. How to read `docker node ls`
Look at the **MANAGER STATUS** column:
- `Leader`: This node is in charge.
- `Reachable`: This node is a backup manager.
- `(empty)`: This is a pure worker node.

### 3. How to read `docker stack ps todo`
This is the most important command for the "Live Demo" feel. Look at the **NODE** column:
- It tells you exactly **which computer** is currently running that specific container.
- If a node fails, you will see Swarm automatically move the task to a different node in this list.

---

## 🛠️ Troubleshooting: "Why are my services failing?"
If you see `Failed` or `exit (1)` in your `docker stack ps` output:
1. **Image Name**: Ensure you have an image tagged as `yourusername/todo-swarm-app:latest`. Swarm needs a specific image name to pull/use.
2. **Volumes**: Swarm is strict with relative paths. Ensure you are running the command from the **project root**.

## 🛠️ Summary of Raw Commands

| Action | Command |
|--------|---------|
| Init Swarm | `docker swarm init` |
| Leave Swarm | `docker swarm leave --force` |
| Deploy Stack | `docker stack deploy -c docker-compose.swarm.yml todo` |
| List Services | `docker stack services todo` |
| List Tasks | `docker stack ps todo` |
| List Nodes | `docker node ls` |
| Remove Stack | `docker stack rm todo` |

---

> **Next Step**: Move to branch **`phase-3-raw`** to handle Secrets manually.
