# 📘 Module 7: Demo — Docker Swarm with Docker-in-Docker

> **Day 2 — Seeing Swarm Work for the First Time**
> Sebelum kita bahas production setup, kita perlu melihat Swarm bekerja secara nyata di depan mata. Di modul ini kita bangun cluster tiga node di laptop masing-masing menggunakan **Docker-in-Docker (DinD)** — tidak perlu VM, tidak perlu VPS, cukup Docker Desktop. Kita deploy aplikasi nyata dari Docker Hub dan lihat ketiga node menjalankan image yang sama. Di akhir modul, kita juga bahas jujur **mengapa DinD bukan production setup** dan apa bedanya dengan cluster sungguhan.

---

## 🎯 Learning Objectives

Setelah menyelesaikan modul ini, peserta akan mampu:

1. Menjelaskan konsep Docker-in-Docker dan cara kerjanya
2. Membangun Swarm cluster tiga node di local machine menggunakan DinD
3. Men-deploy aplikasi dari Docker Hub ke cluster DinD
4. Memverifikasi bahwa semua node menarik image yang sama dari registry
5. Menjelaskan **keterbatasan DinD** dan mengapa tidak cocok untuk production

---

## 1. 🧱 Setup — Apa yang Akan Kita Bangun

```
Laptop kamu (Docker Desktop)
│
├── manager1  (DinD container) ← Swarm Leader
├── worker1   (DinD container) ← Worker node
└── worker2   (DinD container) ← Worker node
        │
        └── semua terhubung via bridge network: swarm-demo
                │
                └── semua pull image dari Docker Hub
```

**Kenapa tiga node?** Dengan tiga manager (atau satu manager dua worker), kita bisa mensimulasikan Raft consensus dan melihat leader election — core concept Swarm yang tidak terlihat dengan satu node saja.

---

## 2. 🐳 Docker-in-Docker — Cara Kerjanya

DinD menjalankan **Docker daemon di dalam Docker container**. Artinya setiap container punya daemon-nya sendiri, image store-nya sendiri, dan network stack-nya sendiri — persis seperti tiga mesin terpisah, tapi semua jalan di laptop kamu.

```
Docker Desktop daemon (host)
│
├── container: manager1
│     └── Docker daemon (isolated)
│           └── bisa jalankan containers di dalamnya
│
├── container: worker1
│     └── Docker daemon (isolated)
│
└── container: worker2
      └── Docker daemon (isolated)
```

> ⚠️ **Penting dipahami**: `docker node ls` yang kamu jalankan di terminal biasa berbicara ke **Docker Desktop daemon** — bukan ke demo Swarm kita. Semua perintah demo harus diawali dengan `docker exec <node>`.

---

## 3. 🚀 Step-by-Step Demo

### Step 1 — Buat Network untuk Cluster

```bash
docker network create swarm-demo
```

Network ini menghubungkan ketiga DinD container sehingga bisa saling berkomunikasi — menggantikan peran physical network di cluster sungguhan.

### Step 2 — Jalankan Tiga Node sebagai Container

```bash
# Manager node
docker run -d --privileged --name manager1 \
  --network swarm-demo \
  docker:dind

# Worker nodes
docker run -d --privileged --name worker1 \
  --network swarm-demo \
  docker:dind

docker run -d --privileged --name worker2 \
  --network swarm-demo \
  docker:dind
```

Flag `--privileged` diperlukan karena DinD perlu akses kernel penuh untuk menjalankan Docker daemon di dalam container.

### Step 3 — Dapatkan IP Address manager1

```bash
docker inspect manager1 \
  --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'
```

Contoh output: `172.17.6.2`

> Simpan IP ini — akan dipakai di semua step berikutnya sebagai `<MANAGER_IP>`.

### Step 4 — Init Swarm di manager1

```bash
docker exec manager1 docker swarm init --advertise-addr <MANAGER_IP>
```

Contoh output:

```
Swarm initialized: current node (wqshe20iz3w...) is now a manager.

To add a worker to this swarm, run the following command:

    docker swarm join --token SWMTKN-1-xxx-yyy <MANAGER_IP>:2377
```

**Simpan token ini.** Dipakai di step berikutnya.

### Step 5 — Join Workers ke Cluster

```bash
docker exec worker1 docker swarm join \
  --token SWMTKN-1-xxx-yyy \
  <MANAGER_IP>:2377

docker exec worker2 docker swarm join \
  --token SWMTKN-1-xxx-yyy \
  <MANAGER_IP>:2377
```

### Step 6 — Verifikasi Cluster

```bash
docker exec manager1 docker node ls
```

Expected output:

```
ID                            HOSTNAME   STATUS    AVAILABILITY   MANAGER STATUS
wqshe20iz3w *                 manager1   Ready     Active         Leader
7dyn1eqdsee                   worker1    Ready     Active
ajb89slppjp                   worker2    Ready     Active
```

✅ Tiga node siap. Satu manager (Leader), dua worker.

---

## 4. 🖼️ Push Demo App ke Docker Hub

Sebelum deploy ke Swarm, image harus tersedia di registry yang bisa diakses semua node. Kita gunakan Docker Hub dengan shared account yang sudah disiapkan.

```bash
# Login ke Docker Hub (gunakan credential yang disediakan instruktur)
docker login

# Clone repo demo
git clone https://github.com/thoriqnf/docker-swarm.git
cd docker-swarm
git checkout phase-1

# Build image
docker build -t <dockerhub-username>/swarm-demo:1.0 .

# Push ke Docker Hub
docker push <dockerhub-username>/swarm-demo:1.0
```

**Kenapa harus push ke Docker Hub?**

```
worker1 daemon   ──── pull ────▶  Docker Hub ✅
worker2 daemon   ──── pull ────▶  Docker Hub ✅

worker1 daemon   ──── pull ────▶  manager1 daemon ❌
                                  (isolated daemon, tidak bisa)
```

Setiap DinD container punya image store sendiri. Worker tidak bisa pull image dari manager — harus dari registry yang bisa diakses ketiganya. Docker Hub adalah solusi paling sederhana.

---

## 5. 🚢 Deploy Service ke Cluster

### Step 1 — Buat Overlay Network

```bash
docker exec manager1 docker network create \
  --driver overlay \
  app-net
```

### Step 2 — Deploy Service

```bash
docker exec manager1 docker service create \
  --name demo-api \
  --replicas 3 \
  --network app-net \
  --publish published=8080,target=3000 \
  <dockerhub-username>/swarm-demo:1.0
```

### Step 3 — Verifikasi Service Berjalan

```bash
# Cek status service
docker exec manager1 docker service ls

# Cek distribusi replica ke nodes
docker exec manager1 docker service ps demo-api
```

Expected output:

```
ID            NAME         IMAGE                        NODE      CURRENT STATE
abc123        demo-api.1   yourusername/swarm-demo:1.0  manager1  Running
def456        demo-api.2   yourusername/swarm-demo:1.0  worker1   Running
ghi789        demo-api.3   yourusername/swarm-demo:1.0  worker2   Running
```

✅ **Semua tiga node pull image yang sama dari Docker Hub dan menjalankannya.**

### Step 4 — Test Routing Mesh

```bash
# Test dari manager1
docker exec manager1 wget -qO- http://localhost:8080

# Test dari worker1 — ingress mesh forward ke replica manapun
docker exec worker1 wget -qO- http://localhost:8080

# Test dari worker2
docker exec worker2 wget -qO- http://localhost:8080
```

Semua node merespons — ini adalah **ingress routing mesh** yang kita pelajari di Module 6. Request ke port 8080 di node manapun akan diteruskan ke salah satu replica.

---

## 6. ⚡ Bonus — Promote Workers ke Manager (HA Simulation)

```bash
# Promote kedua worker menjadi manager
docker exec manager1 docker node promote worker1
docker exec manager1 docker node promote worker2

# Lihat hasilnya
docker exec manager1 docker node ls
```

```
ID             HOSTNAME   STATUS    AVAILABILITY   MANAGER STATUS
wqshe20iz3w *  manager1   Ready     Active         Leader
7dyn1eqdsee    worker1    Ready     Active         Reachable
ajb89slppjp    worker2    Ready     Active         Reachable
```

### Simulasi Leader Failure

```bash
# Kill the Leader
docker stop manager1

# Cek dari surviving manager — election terjadi otomatis
docker exec worker1 docker node ls
```

```
ID             HOSTNAME   STATUS    AVAILABILITY   MANAGER STATUS
wqshe20iz3w    manager1   Down      Active         Unreachable
7dyn1eqdsee *  worker1    Ready     Active         Leader   ← new!
ajb89slppjp    worker2    Ready     Active         Reachable
```

```bash
# Hidupkan kembali manager1
docker start manager1

# manager1 rejoins sebagai Reachable — TIDAK merebut kembali Leader
docker exec manager1 docker node ls
```

> Leader baru tetap menjabat. Tidak ada flip-flop. Ini by design — stabilitas lebih penting dari "siapa yang pertama".

---

## 7. ⚠️ Kenapa DinD BUKAN Production Setup

Ini bagian yang sering dilewatkan di tutorial lain — tapi penting untuk dipahami.

### 7.1 Masalah Keamanan — `--privileged` Flag

```bash
docker run --privileged ...   # ← ini sangat berbahaya di production
```

Flag `--privileged` memberikan container **akses penuh ke host kernel** — termasuk kemampuan mount filesystem, load kernel module, dan mengakses semua device. Di production, ini sama dengan memberi seseorang akses root ke seluruh server.

### 7.2 Tidak Ada Isolasi Network yang Nyata

```
DinD Setup:
  manager1, worker1, worker2 → semua di laptop yang sama
  network "swarm-demo" → hanya bridge network virtual

Production Setup:
  manager, worker1, worker2 → mesin fisik / VM yang berbeda
  network → network interface sungguhan antar host
```

Di DinD, kalau Docker Desktop mati — seluruh cluster mati sekaligus. Tidak ada HA yang sesungguhnya.

### 7.3 Data Tidak Persisten

Container DinD menyimpan data di dalam container layer Docker Desktop. Kalau container di-remove atau Docker Desktop di-reset, semua data hilang.

### 7.4 Performa Tidak Representatif

Menjalankan Docker di dalam Docker menambahkan lapisan overhead yang tidak ada di production. Benchmark, resource usage, dan latency yang kamu ukur di DinD tidak mencerminkan kondisi nyata.

### 7.5 Ringkasan — DinD vs Production

| Aspek | DinD (Demo) | Production (Real Nodes) |
|---|---|---|
| **Tujuan** | Belajar konsep Swarm | Menjalankan workload nyata |
| **Mesin** | Satu laptop | Multiple VM / bare metal |
| **Keamanan** | `--privileged` mode | Standard Docker daemon |
| **HA** | Semu — satu host | Nyata — host berbeda |
| **Network** | Virtual bridge | Physical network |
| **Data** | Hilang kalau reset | Persisten di volume / disk |
| **Performa** | Double overhead | Native Docker |

> 💡 **DinD sangat berguna untuk**: belajar perintah Swarm, memahami konsep clustering, mencoba rolling update dan leader election — tanpa perlu setup infrastruktur. Tapi begitu paham konsepnya, pindah ke setup yang lebih mendekati production.

---

## 8. 🧹 Cleanup

```bash
# Hapus semua node container
docker stop manager1 worker1 worker2
docker rm manager1 worker1 worker2

# Hapus network
docker network rm swarm-demo
```

---

## 📝 Summary

### Key Takeaways

- **DinD** memungkinkan simulasi multi-node Swarm di satu laptop — setiap container punya Docker daemon sendiri yang benar-benar terisolasi.
- **Docker Hub sebagai registry** adalah keharusan di DinD — worker tidak bisa pull image dari daemon lain, harus dari registry yang bisa diakses semua node.
- **Ingress routing mesh** bekerja bahkan di DinD — request ke port manapun di node manapun diteruskan ke replica.
- **Leader election** bisa disimulasikan dengan mematikan manager node — cluster recovery otomatis dalam hitungan detik.
- **DinD tidak cocok untuk production** karena `--privileged` mode, tidak ada isolasi host yang nyata, data tidak persisten, dan performa tidak representatif.
- Semua yang kamu pelajari di sini — service, overlay network, rolling update, Raft — berlaku 100% sama di production cluster dengan node sungguhan.

### Next — Module 8: Maximizing Docker Swarm on a Single Machine

DinD sudah kita pahami dan kita sudah tahu batasannya. Di Module 8 kita masuk ke skenario production yang lebih realistis: **satu mesin powerful** dengan Swarm. Bagaimana mengoptimalkan resource, merancang stack, dan mendapatkan nilai maksimal dari Swarm bahkan tanpa multi-node.

---

## 📎 Referensi

- [Docker Docs — Docker-in-Docker](https://hub.docker.com/_/docker)
- [Docker Docs — docker swarm init](https://docs.docker.com/reference/cli/docker/swarm/init/)
- [Docker Docs — Raft consensus in Swarm](https://docs.docker.com/engine/swarm/raft/)
- [Demo app repository](https://github.com/thoriqnf/docker-swarm/tree/phase-1)