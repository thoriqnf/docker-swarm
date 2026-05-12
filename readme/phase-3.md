# 🐳 Phase 3: Orchestration & Self-Healing (Cluster Logic)

In Phase 3, we explore the "brain" of the cluster. We will learn how Swarm manages node roles, handles quorum, and automatically recovers from container failures.

## 🎖️ 1. Managing Node Roles (Promote/Demote)

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

---

## 🛠️ 2. Node Maintenance (Drain)

Show how to take a server down for maintenance without dropping a single request. Draining a node tells Swarm to move all tasks to other available nodes.

```bash
# Move all tasks off a specific node
docker node update --availability drain <NODE_NAME>

# Verify node status (Availability should be 'Drain')
docker node ls

# Bring it back to active status
docker node update --availability active <NODE_NAME>
```

---

## 🚑 3. Self-Healing Demo

Kill a container manually to show how Swarm's "Reconciliation Loop" works. Swarm always tries to maintain the "Desired State".

```bash
# 1. Find a running container
docker ps

# 2. Kill it!
docker kill <CONTAINER_ID>

# 3. Watch the Magic!
# Swarm will detect the 'failure' and immediately start a new container.
docker stack ps todo
```
*You will see the old container marked as `Failed` and a brand new one already `Running` to replace it.*

---

## ⚖️ 4. Understanding Quorum (Raft)

With 3 managers, your quorum is 2. This means you can lose 1 manager and the cluster stays alive.
- Kill 1 manager → Cluster works ✅
- Kill 2 managers → Cluster frozen ❌ (Split-brain protection)

---

## 🛠️ Summary of Phase 3 Commands

| Action | Command |
|--------|---------|
| Promote Node | `docker node promote <node>` |
| Demote Node | `docker node demote <node>` |
| Drain Node | `docker node update --availability drain <node>` |
| Check Stack Tasks | `docker stack ps todo` |

---

> **Next Step**: Now that the cluster is stable, let's make it bigger! In **Phase 4**, we will learn about **Horizontal Scaling and Rolling Updates**.
