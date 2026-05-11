# 🐳 Phase 1: Local Foundations (Raw Docker Compose)

In Phase 1, we ensure the Laravel 12 application is perfectly containerized and working locally before we touch Swarm. 

Instead of using automation, we will run the **standard Docker Compose commands** to understand exactly how the services are stitched together.

## 🚀 1. Start the Stack

Run the following command to build and start the containers in the background:

```bash
docker compose up -d
```

> [!IMPORTANT]
> Always run `docker compose` commands from the **project root**. Running them from subdirectories (like `docker/`) can lead to "Name does not resolve" errors because the network context and project names might mismatch.

### What's happening?
- **Build**: Docker builds the multi-stage PHP image from `docker/php/Dockerfile`.
- **Network**: A bridge network is created.
- **Volumes**: Local source code is mounted for real-time development.

## 🔑 2. Initial Setup

Once the containers are up, we need to run the standard Laravel setup commands inside the app container:

```bash
# Run migrations and seed data
docker compose exec app php artisan migrate:fresh --seed
```

### Credentials
- **Email**: `demo@example.com`
- **Password**: `password`

## 🎓 3. Manual Health Check

Instead of a script, we can check the app health using `curl` or by visiting the URL:

```bash
# Check the JSON health endpoint
curl -s http://localhost/health
```

You should see a response like:
```json
{
    "status": "ok",
    "services": {
        "database": "connected",
        "redis": "connected"
    },
    "hostname": "container_id"
}
```

## 🛠️ Summary of Raw Commands

| Action | Command |
|--------|---------|
| Start  | `docker compose up -d` |
| Stop   | `docker compose down` |
| Setup  | `docker compose exec app php artisan migrate:fresh --seed` |
| Logs   | `docker compose logs -f` |
| Shell  | `docker compose exec app sh` |

---

> **Next Step**: Move to branch **`phase-2-raw`** to initialize the Swarm cluster manually.
