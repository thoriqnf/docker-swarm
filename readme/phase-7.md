# 🐳 Phase 7 — CI/CD Pipeline Integration

> **Goal:** Automate the entire build, push, and deployment process using GitHub Actions.

Manual deployments are slow and error-prone. In this final phase, we implement a professional CI/CD pipeline that handles testing, building unique images, deploying to Swarm via SSH, and automatically rolling back if anything goes wrong.

---

## 🗺️ 1. The Automated Workflow

Every time you push to the `main` branch, GitHub Actions will:
1.  **Test**: Run automated tests (simulated).
2.  **Build & Push**: Build a new Docker image tagged with the **Commit SHA** and push it to Docker Hub.
3.  **Deploy**:
    *   SSH into your Swarm manager.
    *   Deploy the `docker-stack.yml` using the new image tag.
    *   **Verify**: Monitor the cluster for 120 seconds to ensure all containers reach the "Running" state.
4.  **Auto-Rollback**: If the containers fail to start or the verification times out, the pipeline automatically reverts every service to its previous stable state.

---

## 🔑 2. Prerequisites & Setup

To make the pipeline work, you must configure **GitHub Secrets** in your repository.

### Step 1: Generate SSH Key
On your local machine, generate a dedicated deployment key (do not use your personal key):
```bash
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ./deploy_key -N ""
```
1.  Copy the contents of `deploy_key.pub` and append it to `~/.ssh/authorized_keys` on your server.
2.  Delete the local files after setup.

### Step 2: Add GitHub Secrets
Go to **Settings → Secrets and variables → Actions** and add:

| Secret Name | Description |
| :--- | :--- |
| `DOCKERHUB_USERNAME` | Your Docker Hub username (`thorthede`) |
| `DOCKERHUB_TOKEN` | Docker Hub Access Token (Personal Access Token) |
| `DEPLOY_SSH_KEY` | The contents of the **private** key (`deploy_key`) |
| `DEPLOY_HOST` | The IP address or hostname of your server |
| `DEPLOY_USER` | The SSH username for your server |

---

## 🔄 3. Two-Layer Rollback Mechanism

Our setup provides maximum safety through two layers of protection:

### Layer 1: Swarm-Level (Automatic)
Defined in `docker-stack.yml`. If a container crashes during the update, Swarm detects it and rolls back immediately without waiting for the pipeline.

### Layer 2: Pipeline-Level (Verification)
Defined in `.github/workflows/deploy.yml`. If the deployment "stalls" (e.g., containers stuck in *Pending* due to resource limits), the GitHub Action will time out after 2 minutes and trigger a manual rollback command.

---

## 🔍 4. Verification

After a successful pipeline run, you can verify the status on your server:
```bash
# Check if services are using the new image tag (Commit SHA)
docker service inspect todo_app_app --format '{{.Spec.TaskTemplate.ContainerSpec.Image}}'

# View deployment history
docker service ps todo_app_app
```

---

### 🎉 Conclusion
Congratulations! You have completed the Docker Swarm Roadmap. You now have a scalable, self-healing, and fully automated deployment system ready for production.
