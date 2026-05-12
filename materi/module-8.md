# 📘 Module 8: Maximizing Docker Swarm on a Single Machine

> **Day 2 — Getting Real Value from Swarm Without Multi-Node**
> Di Module 7 kita simulasikan cluster tiga node dengan DinD — dan kita sudah tahu itu bukan production setup. Kenyataannya, banyak tim memulai dengan **satu mesin yang powerful** sebelum scale ke multi-node. Di modul ini kita bahas bagaimana mendapatkan nilai maksimal dari Swarm di satu mesin: resource planning, workload isolation, rolling updates, dan stack management — semua dengan satu Docker daemon sungguhan.

---

## 🎯 Learning Objectives

Setelah menyelesaikan modul ini, peserta akan mampu:

1. Menjelaskan apa yang masih bisa didapat dari Swarm di single node
2. Mengkonfigurasi **resource limits dan reservations** per service
3. Merancang **resource allocation plan** untuk machine dengan spesifikasi tertentu
4. Menggunakan **placement constraints** dan **labels** untuk kontrol workload
5. Men-deploy full application stack dengan `docker stack deploy`
6. Memahami kapan single-node Swarm sudah cukup dan kapan perlu tambah node

---

## 1. 🤔 Single Node — Masalah yang Sebenarnya

### 1.1 Satu Mesin = Satu Point of Failure (dan Itu Oke)

Pertanyaan yang sering muncul: *"Kalau satu mesin mati, semuanya mati — apa gunanya Swarm?"*

Jawaban jujurnya: **untuk high availability host-level, memang tidak ada gunanya**. Kalau mesinnya mati, ya mati semua.

Tapi itu bukan satu-satunya masalah yang Swarm selesaikan:

```
Masalah yang TETAP diselesaikan Swarm di single node:
─────────────────────────────────────────────────────
✅ Satu container crash → auto-restart tanpa intervensi manual
✅ Deploy versi baru → rolling update, tidak ada downtime
✅ Satu service memory leak → tidak bisa "makan" RAM service lain
✅ Semua service didefinisikan dalam satu YAML → reproducible
✅ Traffic tersebar ke multiple replica di mesin yang sama
✅ Nanti tambah node → YAML yang sama langsung bekerja, tanpa rewrite
```

### 1.2 Tanpa Resource Limits — Satu Service Bisa Matikan Semuanya

Ini skenario nyata yang sering terjadi:

```
Mesin: 16 CPU, 128GB RAM — semua service jalan tanpa limits

Senin pagi:
  api service → memory leak, terus naik
  api: 2GB → 8GB → 32GB → 64GB → 100GB

  postgres: tidak bisa dapat memory → query timeout
  redis: di-OOM-kill oleh kernel → cache hilang
  nginx: tidak bisa fork process baru → 502 error

  Semua service mati karena satu service bermasalah ❌
```

Dengan resource limits:

```
  api service → memory leak, terus naik sampai 4GB limit
  → Docker OOM-kill hanya container api yang bermasalah
  → Swarm restart container api (restart policy)
  → Service lain: tidak terdampak sama sekali ✅
```

---

## 2. ⚙️ Resource Limits dan Reservations

### 2.1 Dua Konsep yang Berbeda

```
Reservations  = "Saya butuh minimal ini untuk jalan"
               → dipakai scheduler untuk tentukan apakah node punya cukup resource
               → resource ini "di-hold" untuk service ini

Limits        = "Saya tidak boleh melebihi ini"
               → hard cap yang di-enforce oleh kernel
               → Memory: container di-OOM-kill kalau lewat limit
               → CPU: container di-throttle (diperlambat) kalau lewat limit
```

### 2.2 Cara Konfigurasi di `service create`

```bash
docker service create \
  --name api \
  --replicas 4 \
  --limit-cpu 2 \           # max 2 cores — di-throttle kalau lewat
  --limit-memory 4gb \      # max 4GB RAM — di-OOM-kill kalau lewat
  --reserve-cpu 1 \         # guaranteed 1 core untuk scheduling
  --reserve-memory 2gb \    # guaranteed 2GB untuk scheduling
  yourusername/swarm-demo:1.0
```

### 2.3 Cara Konfigurasi di Stack File (Recommended)

```yaml
services:
  api:
    image: yourusername/swarm-demo:1.0
    deploy:
      replicas: 4
      resources:
        limits:
          cpus: "2"        # string, bukan number
          memory: 4G
        reservations:
          cpus: "1"
          memory: 2G
```

### 2.4 Cara Kerja CPU Limit vs Memory Limit

| | CPU Limit | Memory Limit |
|---|---|---|
| **Mechanism** | cgroups CPU throttling | cgroups OOM killer |
| **Saat dilewati** | Container diperlambat (throttled) | Container di-kill (SIGKILL) |
| **Graceful?** | Ya — proses tetap jalan, hanya lebih lambat | Tidak — langsung mati |
| **Bisa burst?** | Ya — bisa pakai lebih kalau node idle | Tidak — hard cap |

> 💡 **Implikasi untuk aplikasi**: Memory limit harus di-set dengan hati-hati. Kalau aplikasi punya internal memory cache yang tumbuh, pastikan limit cukup tinggi supaya tidak terus-menerus di-kill. Node.js dan JVM perlu perhatian khusus karena punya heap management sendiri — set `--max-old-space-size` (Node) atau `-Xmx` (Java) sedikit di bawah Docker memory limit.

---

## 3. 📊 Resource Planning — 16 Core / 128GB

Ini contoh konkret bagaimana merencanakan resource allocation untuk mesin dengan spesifikasi tersebut.

### 3.1 Alokasi per Service

```
┌─────────────────────────────────────────────────────────┐
│  Komponen                    CPU        RAM              │
├─────────────────────────────────────────────────────────┤
│  OS + Docker daemon          1c         4GB             │
├─────────────────────────────────────────────────────────┤
│  nginx (2 replicas)          1c         1GB             │
├─────────────────────────────────────────────────────────┤
│  api (6 replicas)            6c        24GB             │
├─────────────────────────────────────────────────────────┤
│  worker / background (4x)    4c        16GB             │
├─────────────────────────────────────────────────────────┤
│  postgres                    2c        32GB             │
├─────────────────────────────────────────────────────────┤
│  redis                       1c         8GB             │
├─────────────────────────────────────────────────────────┤
│  monitoring (prometheus dll) 1c         4GB             │
├─────────────────────────────────────────────────────────┤
│  Total dipakai              16c        89GB             │
│  Headroom (buffer spike)     0c        39GB ✅          │
└─────────────────────────────────────────────────────────┘
```

### 3.2 Prinsip Resource Planning

**1. Sisakan headroom**
Jangan alokasikan 100% resource. Sisakan minimal 20–30% untuk spike traffic, system processes, dan deployment yang butuh resource ekstra sementara (start-first rolling update).

**2. Measure dulu, set limit kemudian**
Jangan tebak berapa limit yang dibutuhkan. Jalankan dulu tanpa limit, pantau dengan `docker stats`, baru set limit ~2x dari average usage saat normal load.

```bash
# Pantau usage real-time
docker stats --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}"
```

**3. Reservations ≈ average usage, Limits ≈ peak usage**
```
Contoh api service:
  Average usage: 0.8 CPU, 1.5GB RAM
  Peak usage:    1.8 CPU, 3.2GB RAM

  reservations: cpus=1,    memory=2G   (sedikit di atas average)
  limits:       cpus=2,    memory=4G   (sedikit di atas peak)
```

**4. Stateful service (DB) dapat lebih RAM**
Database sangat terbantu dengan RAM besar untuk buffer pool / page cache. Alokasikan RAM besar untuk Postgres/MySQL daripada untuk API stateless yang bisa di-scale horizontal.

---

## 4. 🏷️ Placement Constraints dan Labels

### 4.1 Kenapa Perlu Placement di Single Node?

Mungkin terdengar aneh — kalau cuma satu node, constraint ke mana? Tapi ada dua alasan tetap berguna:

**Alasan 1 — Persiapan scale out**
Kalau nanti tambah node, constraint sudah ada di stack file. Service yang harus di node tertentu (misalnya DB di node dengan SSD) otomatis landing di tempat yang benar.

**Alasan 2 — Bedakan manager dan workload**
Di single node, manager dan workload jalan di mesin yang sama. Dengan constraint, kamu bisa kontrol mana yang boleh di manager node.

### 4.2 Label Node

```bash
# Tambah labels ke node
docker node update --label-add type=compute manager1
docker node update --label-add ssd=true manager1
docker node update --label-add env=production manager1

# Verifikasi
docker node inspect manager1 --format '{{ .Spec.Labels }}'
# map[env:production ssd:true type:compute]
```

### 4.3 Gunakan Labels di Service

```bash
# Hanya deploy ke node dengan SSD (untuk database)
docker service create \
  --name db \
  --constraint 'node.labels.ssd == true' \
  postgres:16-alpine

# Hanya di worker node (bukan manager)
docker service create \
  --name api \
  --constraint 'node.role == worker' \
  yourusername/swarm-demo:1.0

# Kombinasi beberapa constraint
docker service create \
  --name ml-service \
  --constraint 'node.labels.type == compute' \
  --constraint 'node.labels.env == production' \
  ml-model:latest
```

---

## 5. 🔄 Rolling Updates di Single Node

### 5.1 Zero Downtime Tetap Bisa

Walau single node, rolling update tetap zero-downtime selama kamu pakai `--update-order start-first` dan punya minimal 2 replica:

```bash
docker service update \
  --image yourusername/swarm-demo:2.0 \
  --update-parallelism 2 \      # update 2 replica sekaligus
  --update-delay 10s \          # tunggu 10s antar batch
  --update-order start-first \  # start baru sebelum stop lama
  --update-failure-action rollback \
  api
```

```
Sebelum update: 4 replica v1.0 jalan
                [v1] [v1] [v1] [v1]

Batch 1 (start-first):
  Start 2 replica v2.0 → health check → OK
  Stop 2 replica v1.0
  [v2] [v2] [v1] [v1]  ← traffic tetap jalan

Batch 2:
  Start 2 replica v2.0 → health check → OK
  Stop 2 replica v1.0
  [v2] [v2] [v2] [v2]  ← semua v2.0, tidak ada downtime
```

> ⚠️ **Perhatikan resource saat `start-first`**: sementara ada 6 container jalan (4 lama + 2 baru), resource usage naik. Pastikan ada headroom yang cukup.

### 5.2 Auto-Rollback Kalau Gagal

```bash
docker service update \
  --image yourusername/swarm-demo:broken \
  --update-failure-action rollback \   # auto rollback kalau health check gagal
  --update-monitor 20s \               # monitor 20 detik setelah tiap batch
  api

# Swarm detect container baru unhealthy → rollback otomatis
# Tidak perlu manual intervention
```

---

## 6. 📦 Docker Stack — Kelola Semua Sebagai Satu Unit

### 6.1 Kenapa Stack, Bukan `service create` Satu Per Satu?

```bash
# ❌ Cara lama — sulit di-maintain
docker service create --name nginx ...
docker service create --name api ...
docker service create --name worker ...
docker service create --name db ...
docker service create --name redis ...

# ✅ Cara stack — satu file, satu perintah, version-controlled
docker stack deploy -c docker-stack.yml myapp
```

### 6.2 Contoh Stack File Lengkap — Single Node Production

```yaml
version: "3.8"

services:

  nginx:
    image: nginx:latest
    ports:
      - "80:80"
    deploy:
      replicas: 2
      resources:
        limits:
          cpus: "0.5"
          memory: 512M
        reservations:
          cpus: "0.25"
          memory: 256M
      update_config:
        parallelism: 1
        delay: 10s
        order: start-first
        failure_action: rollback
      restart_policy:
        condition: on-failure
        max_attempts: 3

  api:
    image: yourusername/swarm-demo:1.0
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
        failure_action: rollback
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 3

  worker:
    image: yourusername/swarm-demo:1.0
    command: ["node", "worker.js"]
    deploy:
      replicas: 4
      resources:
        limits:
          cpus: "1"
          memory: 4G
        reservations:
          cpus: "0.5"
          memory: 2G
      restart_policy:
        condition: on-failure
        max_attempts: 5

  postgres:
    image: postgres:15
    volumes:
      - db-data:/var/lib/postgresql/data
    environment:
      POSTGRES_PASSWORD_FILE: /run/secrets/db_password
    secrets:
      - db_password
    deploy:
      replicas: 1
      placement:
        constraints:
          - node.labels.ssd == true      # pin ke node dengan SSD
      resources:
        limits:
          cpus: "4"
          memory: 16G
        reservations:
          cpus: "2"
          memory: 8G
      restart_policy:
        condition: on-failure

  redis:
    image: redis:alpine
    command: redis-server --maxmemory 6gb --maxmemory-policy allkeys-lru
    deploy:
      replicas: 1
      resources:
        limits:
          cpus: "1"
          memory: 8G
        reservations:
          memory: 6G
      restart_policy:
        condition: on-failure

volumes:
  db-data:
    driver: local

secrets:
  db_password:
    external: true
```

### 6.3 Deploy dan Kelola Stack

```bash
# Deploy stack pertama kali
docker stack deploy -c docker-stack.yml myapp

# Cek semua service
docker stack services myapp

# Cek semua task / replica
docker stack ps myapp

# Update — jalankan perintah yang sama setelah edit file
docker stack deploy -c docker-stack.yml myapp

# Hapus seluruh stack (volumes tidak ikut terhapus)
docker stack rm myapp
```

---

## 7. 📈 Menggunakan Replicas untuk Parallelisme

### 7.1 Replicas di Single Node = Concurrent Workers

Di single node, replicas bukan untuk HA — tapi untuk **memanfaatkan semua core**:

```
Mesin: 16 core

Tanpa replicas:
  api: 1 instance, single-threaded Node.js
  → pakai 1 core dari 16 → 15 core idle ❌

Dengan replicas:
  api: 8 instance, masing-masing pakai 1.5 core
  → pakai 12 core dari 16 → jauh lebih efisien ✅
  → Swarm ingress mesh load balance ke 8 instance otomatis
```

### 7.2 Berapa Replica yang Tepat?

Panduan untuk aplikasi stateless (API, worker):

```
CPU-bound app (heavy computation):
  replicas = jumlah CPU yang dialokasikan ÷ limit-cpu per replica
  Contoh: 6 CPU total, limit 1.5 per replica → 4 replicas

I/O-bound app (banyak DB query, HTTP call):
  replicas bisa lebih banyak dari jumlah CPU karena banyak waktu tunggu
  Contoh: 6 CPU total, limit 1.5 per replica → 6-8 replicas

Memory-bound app (caching, ML inference):
  replicas = RAM yang dialokasikan ÷ limit-memory per replica
  Contoh: 24GB total, limit 4GB per replica → 6 replicas
```

---

## 8. 🔭 Dari Single Node ke Multi-Node — Zero Rewrite

Ini adalah "killer feature" dari memulai dengan Swarm di single node:

```bash
# Hari ini: satu node
docker stack deploy -c docker-stack.yml myapp
# Semua jalan di satu mesin

# 6 bulan kemudian: tambah server baru
docker swarm join --token <token> <manager-ip>:2377

# Redeploy stack yang SAMA — tidak ada perubahan YAML
docker stack deploy -c docker-stack.yml myapp
# Swarm otomatis distribusikan replica ke node baru
```

```
Sebelum (1 node):                 Sesudah (2 node):
  manager1: [api][api][api]         manager1: [api][api]
            [api][api][api]         worker1:  [api][api]
                                              [api][api]
```

Tidak ada migration, tidak ada rewrite, tidak ada downtime. Stack file yang sama bekerja di kedua skenario — inilah mengapa mulai dengan Swarm di single node adalah keputusan yang forward-compatible.

---

## 9. ✅ Kapan Single-Node Swarm Sudah Cukup

```
Cukup dengan single node kalau:
  ✅ Traffic masih bisa dilayani satu mesin yang di-scale up
  ✅ Downtime karena mesin mati bisa diterima (bukan 99.99% uptime requirement)
  ✅ Budget tidak cukup untuk multi-node setup
  ✅ Tim kecil, belum butuh kompleksitas multi-node

Sudah perlu tambah node kalau:
  ❌ CPU / RAM sudah di atas 70% average usage walau sudah dioptimasi
  ❌ Downtime saat maintenance window tidak bisa diterima
  ❌ Butuh availability zone redundancy (natural disaster, datacenter outage)
  ❌ Compliance butuh geographic distribution
```

---

## 📝 Summary

### Key Takeaways

- **Single-node Swarm** tetap memberikan nilai nyata: auto-restart, rolling update, resource isolation, dan stack management — walau tanpa multi-host HA.
- **Resource limits** adalah perlindungan utama di single node — mencegah satu service yang bermasalah mematikan seluruh mesin.
- **Reservations untuk scheduling, limits untuk enforcement** — keduanya perlu dikonfigurasi, bukan cukup salah satu.
- **Measure dulu, set limit kemudian** — pakai `docker stats` untuk tahu actual usage sebelum set angka limits.
- **Replicas di single node** bukan untuk HA, tapi untuk memanfaatkan semua CPU core secara paralel.
- **Stack file adalah investasi** — saat waktunya scale ke multi-node, YAML yang sama bekerja tanpa perubahan.
- Sisakan minimal **20–30% headroom** dari total resource — untuk spike, rolling updates, dan proses sistem.

### Next — Module 9: CI/CD Pipeline Integration

Stack sudah jalan di production. Sekarang kita eliminasi deployment manual sepenuhnya — wiring Swarm ke GitHub Actions pipeline untuk build, push, deploy, dan auto-rollback otomatis setiap push ke main branch.

---

## 📎 Referensi

- [Docker Docs — Runtime resource constraints](https://docs.docker.com/config/containers/resource_constraints/)
- [Docker Docs — docker service create — resource flags](https://docs.docker.com/reference/cli/docker/service/create/)
- [Docker Docs — Deploy services to a swarm](https://docs.docker.com/engine/swarm/services/)
- [Docker Docs — docker stack deploy](https://docs.docker.com/reference/cli/docker/stack/deploy/)
- [cgroups — Linux kernel resource management](https://www.kernel.org/doc/html/latest/admin-guide/cgroup-v2.html)