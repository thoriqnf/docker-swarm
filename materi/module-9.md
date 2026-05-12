# 📘 Module 9: CI/CD Pipeline Integration

> **Day 2 — Automating Deployments to Swarm**
> Di Module 7 kita deploy manual lewat `docker exec manager1 docker service create` — dan di Module 8 kita kelola stack lewat `docker stack deploy` dari terminal. Dua cara itu cukup untuk belajar, tapi tidak untuk production. Setiap deploy masih butuh SSH ke server, command manual, dan doa semoga tidak ada yang salah. Di modul ini kita eliminasi semua itu: setiap push ke `main` otomatis build image, push ke Docker Hub, deploy ke Swarm, verify, dan rollback kalau gagal — tanpa satu pun perintah manual.

---

## 🎯 Learning Objectives

Setelah menyelesaikan modul ini, peserta akan mampu:

1. Merancang CI/CD pipeline end-to-end untuk Swarm deployment
2. Mengkonfigurasi **GitHub Actions** workflow untuk build, push, dan deploy
3. Menghubungkan pipeline ke Swarm secara aman via **SSH**
4. Mengimplementasikan **zero-downtime deploy** yang konsisten dengan setup Module 8
5. Mengkonfigurasi **auto-rollback** saat deployment gagal
6. Mengelola **registry credentials** dan deployment secrets di GitHub

---

## 1. 🗺️ Dari Manual ke Otomatis

### 1.1 Yang Kita Lakukan Sekarang (Manual)

```
Developer edit kode
        │
        ▼
docker build -t yourusername/swarm-demo:2.0 .     ← manual
        │
        ▼
docker push yourusername/swarm-demo:2.0            ← manual
        │
        ▼
ssh user@server                                    ← manual
        │
        ▼
docker stack deploy -c docker-stack.yml myapp      ← manual
        │
        ▼
docker stack ps myapp  (cek sendiri apakah berhasil) ← manual
```

Masalahnya bukan hanya lambat — tapi **tidak konsisten**. Setiap orang di tim bisa punya cara deploy yang sedikit berbeda. Kalau ada yang lupa push image dulu sebelum deploy, production jalan image lama tanpa sadar.

### 1.2 Yang Akan Kita Bangun (Otomatis)

```
Developer push ke branch main
        │
        ▼
┌──────────────────────────────────────────────────┐
│               GitHub Actions                     │
│                                                  │
│  Stage 1: Test                                   │
│    └── Run automated tests                       │
│                                                  │
│  Stage 2: Build & Push                           │
│    ├── Build image dengan commit SHA sebagai tag │
│    └── Push ke Docker Hub                        │
│                                                  │
│  Stage 3: Deploy                                 │
│    ├── SSH ke Swarm manager                      │
│    ├── docker stack deploy                       │
│    ├── Verify semua replica running              │
│    └── Auto-rollback kalau gagal                 │
└──────────────────────────────────────────────────┘
        │
        ▼
  Swarm cluster (single node atau multi-node)
  — tidak ada perbedaan dari sudut pandang pipeline
```

---

## 2. 🔑 Setup Sebelum Menulis Pipeline

### 2.1 Yang Dibutuhkan

| Kebutuhan | Sumber |
|---|---|
| Docker Hub credentials | Account yang dipakai di Module 7 |
| SSH key pair untuk CI | Generate baru — jangan pakai personal key |
| IP / hostname server | Server tempat Swarm jalan |
| Stack file | `docker-stack.yml` dari Module 8 |

### 2.2 Generate SSH Key untuk Deployment

```bash
# Generate dedicated key — jangan pakai personal key
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ./deploy_key -N ""
# → deploy_key        (private key → masuk GitHub Secrets)
# → deploy_key.pub    (public key → masuk ke server)

# Copy public key ke Swarm manager
cat deploy_key.pub | ssh user@your-server \
  "cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"

# Hapus file setelah setup — private key hanya boleh ada di GitHub Secrets
rm deploy_key deploy_key.pub
```

### 2.3 Setup GitHub Secrets

**Repository → Settings → Secrets and variables → Actions → New repository secret**

| Secret Name | Nilai | Dari mana |
|---|---|---|
| `DOCKERHUB_USERNAME` | Docker Hub username | Shared account Module 7 |
| `DOCKERHUB_TOKEN` | Docker Hub access token | Docker Hub → Account Settings → Personal access tokens |
| `DEPLOY_SSH_KEY` | Isi private key (`deploy_key`) | Hasil generate di atas |
| `DEPLOY_HOST` | IP atau hostname server | Server Swarm kamu |
| `DEPLOY_USER` | Username SSH di server | User di server |

> 💡 **Gunakan access token, bukan password Docker Hub.** Buat token dengan permission `Read & Write` di Docker Hub → Account Settings → Personal access tokens. Kalau token bocor, kamu bisa revoke tanpa ganti password account.

---

## 3. 📁 Struktur Repository

```
project/
├── .github/
│   └── workflows/
│       └── deploy.yml          ← pipeline definition
├── docker-stack.yml            ← stack file dari Module 8
├── Dockerfile                  ← dari repo demo (phase-1)
├── server.js                   ← aplikasi demo
└── package.json
```

---

## 4. 📝 GitHub Actions Workflow

### 4.1 File Lengkap

```yaml
# .github/workflows/deploy.yml
name: Build & Deploy to Swarm

on:
  push:
    branches: [main]

env:
  IMAGE: ${{ secrets.DOCKERHUB_USERNAME }}/swarm-demo

jobs:

  # ── Stage 1: Test ───────────────────────────────────────────
  test:
    name: Test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: "20"
          cache: "npm"

      - name: Install dependencies
        run: npm ci

      - name: Run tests
        run: npm test

  # ── Stage 2: Build & Push ───────────────────────────────────
  build:
    name: Build & Push Image
    runs-on: ubuntu-latest
    needs: test                  # hanya jalan kalau test lulus
    outputs:
      image_tag: ${{ steps.tag.outputs.tag }}

    steps:
      - uses: actions/checkout@v4

      - name: Set image tag dari commit SHA
        id: tag
        run: echo "tag=sha-${GITHUB_SHA::7}" >> $GITHUB_OUTPUT
        # Contoh: sha-abc1234
        # Setiap build punya tag unik → traceable, tidak ambiguous

      - name: Login ke Docker Hub
        uses: docker/login-action@v3
        with:
          username: ${{ secrets.DOCKERHUB_USERNAME }}
          password: ${{ secrets.DOCKERHUB_TOKEN }}

      - name: Setup Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Build dan push image
        uses: docker/build-push-action@v5
        with:
          context: .
          push: true
          tags: |
            ${{ env.IMAGE }}:${{ steps.tag.outputs.tag }}
            ${{ env.IMAGE }}:latest
          cache-from: type=gha           # pakai GitHub Actions cache
          cache-to: type=gha,mode=max    # hemat waktu build berikutnya

  # ── Stage 3: Deploy ─────────────────────────────────────────
  deploy:
    name: Deploy to Swarm
    runs-on: ubuntu-latest
    needs: build                 # hanya jalan kalau build berhasil

    steps:
      - uses: actions/checkout@v4

      - name: Setup SSH agent
        uses: webfactory/ssh-agent@v0.9.0
        with:
          ssh-private-key: ${{ secrets.DEPLOY_SSH_KEY }}

      - name: Tambah server ke known hosts
        run: ssh-keyscan -H ${{ secrets.DEPLOY_HOST }} >> ~/.ssh/known_hosts

      - name: Copy stack file ke server
        run: |
          scp docker-stack.yml \
            ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }}:/tmp/stack-deploy.yml

      - name: Deploy stack ke Swarm
        env:
          IMAGE_TAG: ${{ needs.build.outputs.image_tag }}
        run: |
          ssh ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }} << EOF
            # Login ke Docker Hub supaya bisa pull image
            echo "${{ secrets.DOCKERHUB_TOKEN }}" | \
              docker login -u "${{ secrets.DOCKERHUB_USERNAME }}" --password-stdin

            # Deploy dengan image tag baru
            IMAGE_TAG=${IMAGE_TAG} \
              docker stack deploy \
                --with-registry-auth \
                -c /tmp/stack-deploy.yml \
                myapp

            # Cleanup
            rm /tmp/stack-deploy.yml
          EOF

      - name: Verify deployment converge
        run: |
          ssh ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }} << 'EOF'
            TIMEOUT=120
            ELAPSED=0
            INTERVAL=10

            echo "Menunggu semua replica running..."

            while [ $ELAPSED -lt $TIMEOUT ]; do
              NOT_RUNNING=$(docker stack ps myapp \
                --filter "desired-state=running" \
                --format "{{.CurrentState}}" \
                | grep -cv "Running" || true)

              if [ "$NOT_RUNNING" -eq "0" ]; then
                echo "✅ Semua replica running!"
                docker stack services myapp
                exit 0
              fi

              echo "⏳ ${NOT_RUNNING} replica belum running... (${ELAPSED}s/${TIMEOUT}s)"
              sleep $INTERVAL
              ELAPSED=$((ELAPSED + INTERVAL))
            done

            echo "❌ Timeout — deployment tidak converge dalam ${TIMEOUT}s"
            docker stack ps myapp
            exit 1
          EOF

      - name: Rollback jika deployment gagal
        if: failure()
        run: |
          echo "🔄 Deployment gagal — trigger rollback..."
          ssh ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }} << 'EOF'
            for service in $(docker stack services myapp --format "{{.Name}}"); do
              echo "Rolling back $service..."
              docker service rollback $service || true
            done
            sleep 20
            echo "📋 Status setelah rollback:"
            docker stack services myapp
          EOF

      - name: Tulis deployment summary
        if: always()
        env:
          IMAGE_TAG: ${{ needs.build.outputs.image_tag }}
        run: |
          cat >> $GITHUB_STEP_SUMMARY << EOF
          ## 🚀 Deployment Summary

          | | |
          |---|---|
          | **Status** | ${{ job.status }} |
          | **Image** | \`${{ env.IMAGE }}:${IMAGE_TAG}\` |
          | **Commit** | \`${{ github.sha }}\` |
          | **Branch** | ${{ github.ref_name }} |
          | **Triggered by** | ${{ github.actor }} |
          EOF
```

---

## 5. 🐳 Stack File yang CI/CD-Friendly

Sedikit adjustment dari stack file Module 8 supaya bisa menerima `IMAGE_TAG` dari pipeline:

```yaml
# docker-stack.yml
version: "3.8"

services:
  api:
    # IMAGE_TAG di-inject dari environment variable pipeline
    # Kalau tidak ada → fallback ke "latest"
    image: yourusername/swarm-demo:${IMAGE_TAG:-latest}
    deploy:
      replicas: 6
      resources:
        limits:
          cpus: "1.5"
          memory: 4G
        reservations:
          cpus: "1"
          memory: 2G
      update_config:
        parallelism: 2
        delay: 10s
        order: start-first
        failure_action: rollback      # ← Swarm-level auto-rollback
        monitor: 20s
      rollback_config:
        parallelism: 2
        delay: 5s
        order: start-first
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 3

  # ... service lain dari Module 8
```

**Kenapa commit SHA sebagai tag, bukan `latest`?**

```
Problem dengan latest:
  Deploy 1: push image "latest" → worker pull "latest" → v1.0 jalan ✅
  Deploy 2: push image "latest" → worker pull "latest"
            → tapi worker punya "latest" di cache → tetap jalan v1.0 ❌

Dengan sha-abc1234:
  Deploy 1: push "sha-abc1234" → worker pull → v1.0 jalan ✅
  Deploy 2: push "sha-def5678" → worker pull → image baru karena tag berbeda ✅
  Rollback: deploy "sha-abc1234" lagi → worker pull → kembali ke v1.0 ✅
```

---

## 6. 🔄 Dua Layer Rollback

Pipeline kita punya dua mekanisme rollback yang saling melengkapi:

### Layer 1 — Swarm-Level (Otomatis saat Rolling Update)

Dikonfigurasi di `docker-stack.yml`:
```yaml
update_config:
  failure_action: rollback   # kalau container baru unhealthy, Swarm rollback sendiri
  monitor: 20s               # monitor 20 detik setelah tiap batch
```

Trigger: container baru crash atau health check gagal saat rolling update berlangsung. Tidak butuh pipeline untuk trigger ini — Swarm yang handle.

### Layer 2 — Pipeline-Level (Otomatis saat Verify Gagal)

Dikonfigurasi di GitHub Actions:
```yaml
- name: Rollback jika deployment gagal
  if: failure()   # jalan kalau verify step timeout atau error
```

Trigger: semua replica tidak converge dalam 120 detik. Pipeline yang eksekusi `docker service rollback`.

```
Push ke main
    │
    ▼
Build & push image SHA ──── gagal ──▶ Pipeline stop, tidak deploy ✅
    │
    ▼
docker stack deploy
    │
    ├── Container baru crash? ──▶ Swarm Layer 1 rollback otomatis
    │
    ▼
Verify 120 detik
    │
    ├── Timeout? ──▶ Pipeline Layer 2 rollback
    │
    ▼
✅ Deploy sukses — semua replica running
```

---

## 7. 🌿 Branch Strategy

### 7.1 Setup Sederhana untuk Tim Kecil

```
feature/xxx  →  push → run tests only (tidak deploy)
main         →  push → build + deploy ke production
```

```yaml
on:
  push:
    branches: [main]
```

### 7.2 Dengan Staging Environment

```
develop  →  push → build + deploy ke staging server
main     →  push → build + deploy ke production server
```

```yaml
on:
  push:
    branches: [main, develop]

jobs:
  deploy:
    steps:
      - name: Set target server
        run: |
          if [ "${{ github.ref_name }}" == "main" ]; then
            echo "DEPLOY_HOST=${{ secrets.PROD_HOST }}" >> $GITHUB_ENV
            echo "STACK_NAME=myapp-prod" >> $GITHUB_ENV
          else
            echo "DEPLOY_HOST=${{ secrets.STAGING_HOST }}" >> $GITHUB_ENV
            echo "STACK_NAME=myapp-staging" >> $GITHUB_ENV
          fi
```

---

## 8. 🔍 Melihat Pipeline Berjalan

### 8.1 Di GitHub

```
Repository → Actions → pilih workflow run

Setiap stage bisa diklik untuk lihat log detail:
  ✅ Test (30s)
  ✅ Build & Push (2-3 menit, lebih cepat dengan cache)
  ✅ Deploy (30-60s)
     └── Copy stack file
     └── docker stack deploy → output nama services
     └── Verify → polling setiap 10 detik
     └── Summary: image tag, commit SHA, siapa yang trigger
```

### 8.2 Di Server — Verifikasi Manual Setelah Pipeline

```bash
# Cek semua service dan replica count
docker stack services myapp

# Cek image yang sedang dipakai — harus match commit SHA
docker service inspect myapp_api \
  --format '{{.Spec.TaskTemplate.ContainerSpec.Image}}'
# yourusername/swarm-demo:sha-abc1234

# Cek task history
docker service ps myapp_api

# Stream log real-time
docker service logs --follow --tail 50 myapp_api
```

---

## 9. ⚠️ Common Pitfalls

| Masalah | Penyebab | Solusi |
|---|---|---|
| SSH connection refused | Port 22 tertutup atau IP salah | Verifikasi `DEPLOY_HOST` secret, cek firewall |
| Permission denied (SSH) | Public key belum di `authorized_keys` | Ulangi step setup SSH key |
| Image tidak ketemu di worker | `--with-registry-auth` tidak dipakai | Tambahkan flag ke `docker stack deploy` |
| Deployment tidak converge | Health check terlalu strict atau app lambat start | Tambah `start_period` di healthcheck, perpanjang verify timeout |
| Stack deploy tidak update service | Image tag tidak berubah | Pastikan `IMAGE_TAG` di-pass dengan benar, verifikasi SHA berbeda |
| Rollback tidak ada previous spec | Deploy pertama kali, belum ada history | Skip rollback untuk initial deploy |

---

## 📝 Summary

### Key Takeaways

- **Tiga stage pipeline**: test → build & push → deploy. Setiap stage hanya jalan kalau stage sebelumnya sukses (`needs:`).
- **Commit SHA sebagai image tag** — setiap build traceable, tidak ada cache issue dengan `latest`.
- **`--with-registry-auth`** wajib di `docker stack deploy` supaya semua node bisa pull dari registry yang memerlukan login.
- **Dua layer rollback**: Swarm-level untuk kegagalan saat rolling update, pipeline-level untuk kegagalan convergence. Keduanya otomatis.
- **Stack file dari Module 8 langsung dipakai** — hanya perlu tambahkan `${IMAGE_TAG:-latest}` di image tag. Tidak ada perubahan lain.
- **Single node atau multi-node** tidak ada perbedaan dari sudut pandang pipeline — `docker stack deploy` bekerja sama di keduanya.

### Next — Module 10: Swarm vs Kubernetes

Cluster sudah production-ready dengan CI/CD otomatis. Di Module 10 kita mundur dan lihat gambaran besar: kapan Swarm adalah pilihan yang tepat, kapan Kubernetes lebih masuk akal, dan bagaimana memutuskan berdasarkan kondisi tim dan organisasi — bukan hanya fitur checklist.

---

## 📎 Referensi

- [GitHub Actions — Documentation](https://docs.github.com/en/actions)
- [GitHub Actions — docker/build-push-action](https://github.com/docker/build-push-action)
- [GitHub Actions — docker/login-action](https://github.com/docker/login-action)
- [GitHub Actions — webfactory/ssh-agent](https://github.com/webfactory/ssh-agent)
- [Docker Hub — Access tokens](https://docs.docker.com/docker-hub/access-tokens/)
- [Docker Docs — docker stack deploy](https://docs.docker.com/reference/cli/docker/stack/deploy/)