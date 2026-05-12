# 🐳 Phase 7 — CI/CD Pipeline Integration

> **Goal:** Automate the entire build, push, and deployment process using GitHub Actions.

Manual deployments are slow and error-prone. In this final phase, we implement a professional CI/CD pipeline that handles testing, building unique images, deploying to Swarm via SSH, and automatically rolling back if anything goes wrong.

---

## 🗺️ 1. The Automated Workflow

Every time you push to the `main` branch, GitHub Actions will automate the deployment. 

> [!TIP]
> **No Server? No Problem!**
> If you don't have a VPS, jump to **[Section 5: 🏠 Full Local Simulation](#-5-full-local-simulation)** to run this entire pipeline on your Mac using your existing DinD cluster.

### The Pipeline Steps:
1.  **Test**: Run automated tests.
2.  **Build & Push**: Build a new Docker image tagged with the **Commit SHA** and push it to Docker Hub (or your Local Registry).
3.  **Deploy**: SSH into your Swarm manager (or use `docker exec` locally).
4.  **Auto-Rollback**: If the containers fail to start, the pipeline automatically reverts every service.

---

## 🔑 2. Prerequisites & Setup

To make the pipeline work, you must configure **GitHub Secrets** in your repository.

### Step 1: Generate SSH Key
On your local machine, generate a dedicated deployment key (do not use your personal key):
```bash
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ./deploy_key -N ""
```

1.  **Extract the Public Key**:
    Run `cat ./deploy_key.pub` and copy the output (starts with `ssh-ed25519...`).
2.  **Authorize on Server**:
    SSH into your server and append that copied string to the end of `~/.ssh/authorized_keys`.
    ```bash
    # Example command on your server:
    echo "PASTE_YOUR_PUBLIC_KEY_HERE" >> ~/.ssh/authorized_keys
    ```
3.  **Cleanup**:
    Once you've added the **Private Key** to GitHub Secrets (Step 2) and the **Public Key** to your server, delete the local files for security:
    ```bash
    rm deploy_key deploy_key.pub
    ```

### Step 2: Add GitHub Secrets
Go to **Settings → Secrets and variables → Actions** and add:

| Secret Name | Description |
| :--- | :--- |
| `DOCKERHUB_USERNAME` | Your Docker Hub username (`thorthede`) |
| `DOCKERHUB_TOKEN` | Docker Hub Access Token (Personal Access Token) |
| `DEPLOY_SSH_KEY` | The contents of the **private** key (`deploy_key`) |
| `DEPLOY_HOST` | The IP address or hostname of your server |
| `DEPLOY_USER` | The SSH username for your server |

### Step 3: Create the Workflow File
Create a new file in your repository at `.github/workflows/deploy.yml`. This file contains the "brain" of your automation.

**Key Logic in the Workflow:**
*   **Unique Tagging**: It uses the first 7 characters of the Git SHA (e.g., `sha-a1b2c3d`) as the image tag. This ensures every deployment is unique and traceable.
*   **Zero-Downtime Update**: It runs `docker stack deploy --with-registry-auth` to update services one by one.
*   **The Verifier**: A custom bash script monitors the containers for 120 seconds. If any container stays in `Pending` or `Starting` too long, it marks the job as failed.
*   **The Fail-Safe**: If the Verifier fails, the `Rollback` step triggers automatically, running `docker service rollback` for every service in the stack.

---

## 🛡️ 3. The Two-Layer Safety Net

Our pipeline isn't just "push and pray." It uses two layers of protection:

1.  **Swarm-Level (Native)**: If a container crashes immediately on start, Docker Swarm's `rollback_config` (defined in `docker-stack.yml`) will stop the rollout.
2.  **Pipeline-Level (Custom)**: If the deployment is "stuck" (e.g., your server ran out of RAM, so the new containers can't even start), the GitHub Action detects the timeout and force-reverts the stack to the previous working version.

---

## 🔍 4. Verification & Testing

Once you push your code to `main`, go to the **Actions** tab in GitHub to watch the magic happen. After it finishes:

1.  **Check Service Image**:
    Verify the service is running the new SHA tag:
    ```bash
    docker service inspect todo_app_app --format '{{.Spec.TaskTemplate.ContainerSpec.Image}}'
    ```

2.  **View Task History**:
    See the history of deployments and rollbacks:
    ```bash
    docker stack ps todo_app
    ```

---
 
## 🏠 5. Full Local Simulation

If you don't have a VPS, you can simulate this entire pipeline on your Mac. We will use a **Local Registry** to act as our "Docker Hub" and your `manager` container to act as the "Server."

### 1. Start a Local Registry
Run this on your Mac to start a private image storage:
```bash
docker run -d -p 5001:5000 --restart=always --name local-registry registry:2
```

### 2. Configure DinD to use the Registry
Your DinD nodes (manager/workers) need to know that this registry is safe. Run this for each node (manager, worker1, worker2):
```bash
docker exec -it manager sh -c "echo '{\"insecure-registries\":[\"host.docker.internal:5001\"]}' > /etc/docker/daemon.json && kill -SIGHUP 1"
```
*(Repeat for `worker1` and `worker2` if you have them).*

### 3. Run the Local Pipeline
I have created a `local-deploy.sh` script in your project root. This script performs the Build → Push → Deploy → Verify → Rollback cycle automatically.

```bash
chmod +x local-deploy.sh
./local-deploy.sh
```

---

## 🎬 6. Local GitHub Actions (Simulation)

If you want to use the **actual `.github/workflows/deploy.yml`** file locally instead of a custom script, you can use a tool called `act`.

### 1. Install `act`
```bash
brew install act
```

### 2. Configure Secrets
Create a `.secrets` file in your root folder:
```env
DOCKERHUB_USERNAME=your_username
DOCKERHUB_TOKEN=your_token
DEPLOY_SSH_KEY="-----BEGIN OPENSSH PRIVATE KEY-----
... paste your key here ...
-----END OPENSSH PRIVATE KEY-----"
DEPLOY_HOST=host.docker.internal
DEPLOY_USER=your_mac_username
```

### 3. Enable Local SSH
Ensure **Remote Login** is enabled in your Mac's System Settings and your key is authorized:
```bash
cat deploy_key.pub >> ~/.ssh/authorized_keys
```

### 4. Run the Action
```bash
act push --secret-file .secrets
```

---

## 🧹 7. Cleanup
Once you are done with the demo, you can remove the local registry and the keys:
```bash
docker rm -f local-registry
rm deploy_key deploy_key.pub .secrets docker-stack.local.yml
```

**What happens during the simulation?**
1.  **Build**: It builds the app image on your Mac.
2.  **Push**: It sends the image to `localhost:5001` (your local registry).
3.  **Deploy**: It tells the `manager` container to pull the image from `host.docker.internal:5001` and update the stack.
4.  **Verify**: It polls the `manager` every 5 seconds to check if containers are healthy.
5.  **Rollback**: If you intentionally break the code (e.g., a typo in the DB password) and run the script, you will see it detect the failure and automatically revert to the previous version!

---

### 🎉 Conclusion
Congratulations! You have completed the Docker Swarm Roadmap. You now have a scalable, self-healing, and fully automated deployment system ready for production.
