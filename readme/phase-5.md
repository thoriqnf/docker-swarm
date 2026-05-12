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

### Step 1: Create the Demo Network
Create a bridge network so the DinD containers can communicate with each other.
```bash
docker network create swarm-demo
```

### Step 2: Spin Up DinD Nodes
Run three containers with the `docker:dind` image. The `--privileged` flag is required for the nested Docker daemon to work.
```bash
# Start Manager
docker run -d --privileged --name manager1 --network swarm-demo docker:dind

# Start Workers
docker run -d --privileged --name worker1 --network swarm-demo docker:dind
docker run -d --privileged --name worker2 --network swarm-demo docker:dind
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
3. Get the join token (it will be printed in the previous output, but you can retrieve it again):
   ```bash
   JOIN_TOKEN=$(docker exec manager1 docker swarm join-token worker -q)
   ```

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
   # Use the phase-1 code for the demo
   git checkout phase-1
   
   # Build the image
   docker build -t thorthede/swarm-demo:1.0 .
   
   # Push to Docker Hub
   docker login
   docker push thorthede/swarm-demo:1.0
   ```

2. **Deploy Service to Swarm**:
   ```bash
   # Switch back to phase-5 instructions
   git checkout phase-5
   
   # Create an overlay network inside the cluster
   docker exec manager1 docker network create --driver overlay app-net
   
   # Deploy the service with 3 replicas
   docker exec manager1 docker service create \
     --name demo-api \
     --replicas 3 \
     --network app-net \
     --publish published=8080,target=80 \
     thorthede/swarm-demo:1.0
   ```

---

## ⚡ 4. Live Demo: HA & Self-Healing

### Test the Routing Mesh
Run this multiple times to see the request hitting different nodes:
```bash
docker exec manager1 wget -qO- http://localhost:8080
```

### Simulate Node Failure
1. Promote workers to managers for HA (simulating Raft Quorum):
   ```bash
   docker exec manager1 docker node promote worker1 worker2
   ```
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
```bash
docker stop manager1 worker1 worker2
docker rm manager1 worker1 worker2
docker network rm swarm-demo
```
