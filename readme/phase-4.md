# 🐳 Phase 4: Orchestration & Self-Healing (Raw CLI)

In Phase 4, we demonstrate why Swarm is an "Orchestrator". We will scale services, perform node maintenance, and show how the cluster auto-heals when things break.

## 📈 1. Scaling Live

Scale the application service to 10 replicas and watch Swarm distribute them:

```bash
# Scale up
docker service scale todo_app=10

# Watch the tasks spread across nodes in real-time
# (If 'watch' is not installed, use the loop below)
watch docker stack ps todo

# macOS/Linux Alternative (Zsh/Bash):
while true; do clear; docker stack ps todo; sleep 2; done

# Windows Alternative (PowerShell):
while($true) { Clear-Host; docker stack ps todo; Start-Sleep -Seconds 2 }
```

## 🛠️ 2. Node Maintenance (Drain)

Show how to take a server down for maintenance without dropping a single request:

```bash
# Move all tasks off a specific node
docker node update --availability drain <NODE_NAME>

# Verify node status
docker node ls

# Bring it back later
docker node update --availability active <NODE_NAME>
```

## 🚑 3. Self-Healing Demo

Kill a container manually to show how Swarm's "Reconciliation Loop" works.

```bash
# 1. Find a running container
docker ps

# 2. Kill it!
docker kill <CONTAINER_ID>

# 3. Check the service status immediately
docker stack ps todo
```
*You will see the old container marked as `Failed` and a brand new one already `Running` to replace it.*

## 🎖️ 3. Changing Roles (Promote/Demote)

In a resilient cluster, you want multiple **Managers** so that if one fails, the cluster keeps running (Raft Consensus).

### Promote a Worker to Manager
Turn a "muscle" node into a "brain" node:
```bash
docker node promote <NODE_NAME>
```

### Demote a Manager to Worker
If a node is no longer needed for management, turn it back into a pure worker:
```bash
docker node demote <NODE_NAME>
```

> [!TIP]
> Use `docker node ls` after these commands to see the **MANAGER STATUS** column change!

## 🛠️ Summary of Raw Commands

| Action | Command |
|--------|---------|
| Scale Service | `docker service scale <name>=<n>` |
| Drain Node | `docker node update --availability drain <node>` |
| Promote Node | `docker node promote <node>` |
| Demote Node | `docker node demote <node>` |
| Service Logs | `docker service logs -f <name>` |

---

> **Final Step**: You have completed Day 1 (Phases 1–4). Ready for Day 2?
