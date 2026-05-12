# 🐳 Phase 5 — Demo: Docker Swarm with Docker-in-Docker (DinD)

> **Goal:** Build a 3-node Swarm cluster on a single machine using DinD to simulate a multi-node production environment.

In this phase, we move beyond a single-node setup and simulate a real cluster (1 Manager, 2 Workers) using **Docker-in-Docker**. This allows us to see how Swarm handles node roles, image distribution via a registry, and self-healing in a multi-node context.

---

## 🏗️ 1. Cluster Architecture

```text
Host Machine (Docker Desktop)
│
├── manager1  (DinD Container) → Swarm Leader
├── worker1   (DinD Container) → Worker Node
└── worker2   (DinD Container) → Worker Node
        │
        └── Connected via bridge network: swarm-demo
```

---

## 🚀 2. Step-by-Step Implementation

### Step 0: Fresh Start (Optional)
If you have run this demo before or see "already exists" errors, run this to clear your environment:
```bash
# Force remove containers and network
docker rm -f manager1 worker1 worker2 2>/dev/null
docker network rm swarm-demo 2>/dev/null
```

### Step 1: Create the Demo Network
Create a bridge network so the DinD containers can communicate with each other.
```bash
docker network create swarm-demo
```

### Step 2: Spin Up DinD Nodes
Run three containers with the `docker:dind` image. The `--privileged` flag is required for nested Docker, and `--hostname` ensures Swarm uses friendly node names.
```bash
# Start Manager
docker run -d --privileged --name manager1 --hostname manager1 --network swarm-demo docker:dind

# Start Workers
docker run -d --privileged --name worker1 --hostname worker1 --network swarm-demo docker:dind
docker run -d --privileged --name worker2 --hostname worker2 --network swarm-demo docker:dind
```

### Step 3: Initialize the Swarm Cluster
1. Get the IP address of `manager1` within the `swarm-demo` network:
   ```bash
   MANAGER_IP=$(docker inspect manager1 --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}')
   echo $MANAGER_IP
   ```
2. Initialize Swarm on `manager1`:
   ```bash
   docker exec manager1 docker swarm init --advertise-addr $MANAGER_IP
   ```
   > [!TIP]
   > If you get an error saying `MANAGER_IP` is empty, check if the container is running: `docker ps`.
   > If initialization fails with "already part of a swarm", it means the container wasn't cleaned up from a previous run. Run Step 0 again.

3. Get the join token (it will be printed in the previous output, but you can retrieve it again):
   ```bash
   JOIN_TOKEN=$(docker exec manager1 docker swarm join-token worker -q)
   ```

   > [!WARNING]
   > **Error: "This node is not a swarm manager"**?
   > This means the `docker swarm init` in Step 2 failed or didn't run. 
   > 1. Verify your IP: `echo $MANAGER_IP` (should not be empty).
   > 2. Manually run the init: `docker exec manager1 docker swarm init --advertise-addr <YOUR_MANAGER_IP>`.
   > 3. Verify manager status: `docker exec manager1 docker node ls`.

### Step 4: Join Workers to the Cluster
Connect the worker nodes to the manager using the token and IP address.
```bash
docker exec worker1 docker swarm join --token $JOIN_TOKEN $MANAGER_IP:2377
docker exec worker2 docker swarm join --token $JOIN_TOKEN $MANAGER_IP:2377
```

### Step 5: Verify the Cluster
Check the status of all nodes from the manager:
```bash
docker exec manager1 docker node ls
```
You should see 3 nodes: `manager1` (Leader), `worker1` (Ready), and `worker2` (Ready).

---

## 🖼️ 3. Image Distribution (Docker Hub)

Since each DinD container has its own isolated image store, they cannot "see" images built on your host machine. We must use a registry (Docker Hub) so every node can pull the same image.

1. **Build & Push from Host**:
   ```bash
   # Build the application image using the specific Dockerfile
   docker build -t thorthedev/swarm-demo:1.0 -f docker/php/Dockerfile .
   
   # Push to Docker Hub
   docker login
   docker push thorthedev/swarm-demo:1.0
   ```

2. **Deploy Service to Swarm**:
   We will use `nginx:alpine` for this infrastructure demo. It starts instantly and doesn't require a database, making it perfect for testing Swarm's HA and routing mesh.

   ```bash
   # Create an overlay network inside the cluster
   docker exec manager1 docker network create --driver overlay app-net
   
   # Deploy the service with 3 replicas
   docker exec manager1 docker service create \
     --name demo-api \
     --replicas 3 \
     --network app-net \
     --publish published=8080,target=80 \
     nginx:alpine
   ```

   > [!NOTE]
   > **Why not use the Laravel image yet?**
   > Your Laravel image (built in Step 1) is designed to wait for a MySQL database. Since we haven't deployed MySQL to the Swarm yet, the app would get stuck in a "Waiting for database" loop. We will deploy the full stack in Phase 7.

    > [!TIP]
    > **Stuck at "0 out of 3 tasks"?**
    > This usually means the worker nodes are having trouble pulling your image.
    > 1. **Check Error Message**:
    >    ```bash
    >    docker exec manager1 docker service ps demo-api --no-trunc
    >    ```
    >    Look at the `ERROR` column. If you used your Laravel image, it might say `task: non-zero exit (137)`, which means it ran out of memory waiting for a database.
    > 2. **Check Workers**: Ensure workers are `Ready`:
    >    ```bash
    >    docker exec manager1 docker node ls
    >    ```
    > 3. **Public vs Private**: If your Docker Hub repo is **Private**, you must add `--with-registry-auth` to the `service create` command so workers can use your credentials.

---

## ⚡ 4. Live Demo: HA & Self-Healing

### Test the Routing Mesh
Run this multiple times. Swarm will balance the requests across all 3 nodes. Since we are using Nginx, you will see the HTML source of the "Welcome to nginx!" page:
```bash
# Get the internal IP of the manager node
MANAGER_IP=$(docker inspect manager1 --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}')

# Test the connection (using IP is more reliable in DinD than localhost)
docker exec manager1 wget -qO- http://$MANAGER_IP:8080
```

### Simulate Node Failure
1. Promote workers to managers for HA (simulating Raft Quorum):
   ```bash
   docker exec manager1 docker node promote worker1 worker2
   ```
   > [!TIP]
   > If you see "node not found", run `docker exec manager1 docker node ls` to check the actual hostnames or use the Node IDs.
2. Kill the current leader:
   ```bash
   docker stop manager1
   ```
3. Check status from another node to see the new Leader election:
   ```bash
   docker exec worker1 docker node ls
   ```

---

## ⚠️ 5. Why DinD is NOT for Production
- **Privileged Mode**: Gives the container full access to the host kernel (Security Risk).
- **No Real Isolation**: One host failure kills the whole "cluster".
- **Non-Persistent**: Data is lost when containers are removed.
- **Performance**: High overhead due to nested virtualization.

---

## 🧹 6. Cleanup
To stop the demo and free up resources:
```bash
docker rm -f manager1 worker1 worker2
docker network rm swarm-demo
```
