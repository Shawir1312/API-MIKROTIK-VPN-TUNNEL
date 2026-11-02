# API-MIKROTIK-VPN-TUNNEL

> **MikroTik VPN Remote & Tunnel Gateway** — Aplikasi Web Management VPN Tunnel & Remote MikroTik berbasis **100% Full Pure PHP** dengan IP Publik dan DST-NAT Port Forwarding otomatis.

---

## 🌟 Ikhtisar Proyek

Aplikasi ini dirancang khusus untuk memecahkan kendala klasik jaringan ISP / RT-RW Net: **MikroTik klien berada di belakang NAT operator seluler / ISP tanpa IP Publik**, sehingga tidak bisa diremote dari luar.

Dengan sistem ini, Anda dapat menghubungkan banyak MikroTik cabang ke VPS Gateway yang memiliki **1 IP Publik**, kemudian mengarahkan port publik (DST-NAT) ke masing-masing MikroTik sehingga dapat diakses via:
- 🖥️ **Winbox Remote** (`IP_PUBLIK:PORT_WINBOX`)
- 🌐 **WebFig Dashboard** (`IP_PUBLIK:PORT_WEBFIG`)
- ⚙️ **RouterOS API** (`IP_PUBLIK:PORT_API`)
- 📟 **SSH Terminal** (`IP_PUBLIK:PORT_SSH`)
- 📹 **Akses Kamera CCTV / ONT WebGUI di jaringan lokal LAN cabang**

Semua protokol VPN didukung: **WireGuard (RouterOS v7)**, **SSTP (RouterOS v6 & v7)**, dan **L2TP/IPSec**.

---

## 🚀 Fitur Unggulan

1. **100% Full Pure PHP (Zero External Runtime)**
   - Tidak memerlukan Node.js, Python, ataupun daemon background khusus.
   - Menggunakan soket biner murni (`fsockopen` / stream) untuk komunikasi protokol RouterOS API v6 & v7.
2. **Modern Web Installer (`install.php`)**
   - Wizard instalasi otomatis 3 langkah: Cek ekstensi server, tes koneksi MySQL & auto-create database/tabel, setup akun Super Admin, dan konfigurasi IP Publik VPS.
3. **1-Click Script Generator MikroTik**
   - Menghasilkan script konfigurasi MikroTik lengkap dengan 1 kali klik. Tinggal paste di Terminal MikroTik, tunnel langsung UP!
4. **Alokasi Otomatis IP Tunnel & Port Publik**
   - Otomatis mencarikan IP lokal tunnel yang belum dipakai (misal `10.88.0.2`, `10.88.0.3`, dst).
   - Otomatis memilih port publik DST-NAT yang kosong (misal `10001`, `10002`).
5. **Dukungan Multi-Gateway (Linux iptables & MikroTik CHR)**
   - Mendukung Linux VPS (rule otomatis `iptables -t nat -A PREROUTING ... -j DNAT`).
   - Mendukung MikroTik CHR Gateway (rule otomatis `/ip firewall nat add ... action=dst-nat`).
6. **Live Monitoring MikroTik via Socket API**
   - Inspeksi CPU load, free memory, uptime, latency, dan RouterOS resource secara realtime langsung dari web.
7. **Preset Port Forwarding Cepat**
   - Tombol 1-klik untuk Winbox (8291), WebFig (80/443), API (8728), SSH (22), dan CCTV/LAN.
8. **Responsive Bootstrap 5 Dark UI**
   - Tampilan modern, bersih, responsif di HP/Desktop, serta dilengkapi tombol 1-klik salin URL akses.

---

## 📐 Topologi & Alur Arsitektur

```
[ INTERNET / ADMIN ]
         │
         │ (Akses: 103.180.200.5:10001)
         ▼
┌────────────────────────────────────────────────────────┐
│  VPS GATEWAY (IP Publik: 103.180.200.5)               │
│                                                        │
│  [ Web Management & Installer (Full PHP) ]             │
│  [ DST-NAT Forwarding Engine ]                         │
│     - Port 10001 ──(DNAT)──> 10.88.0.2:8291 (Winbox)   │
│     - Port 10002 ──(DNAT)──> 10.88.0.2:80   (WebFig)   │
│     - Port 10003 ──(DNAT)──> 10.88.0.3:8291 (Winbox)   │
│     - Port 10004 ──(DNAT)──> 10.88.0.2:8080 (CCTV LAN) │
└───────────────────────┬────────────────────────────────┘
                        │
             WireGuard / SSTP / L2TP Tunnel
                        │
         ┌──────────────┴──────────────┐
         ▼                             ▼
┌──────────────────┐          ┌──────────────────┐
│ MikroTik Cabang A│          │ MikroTik Cabang B│
│ IP: 10.88.0.2    │          │ IP: 10.88.0.3    │
│ (NAT GSM / Fiber)│          │ (NAT GSM / Fiber)│
└──────────────────┘          └──────────────────┘
```

---

## 🛠️ Cara Instalasi Cepat

### 1. Clone Repositori
```bash
cd /www/wwwroot
git clone https://github.com/Shawir1312/API-MIKROTIK-VPN-TUNNEL.git vpn-tunnel
cd vpn-tunnel
chown -R www:www .
chmod -R 755 .
```

### 2. Jalankan Web Installer Wizard
Buka browser dan akses domain atau IP server Anda:
```
http://IP_VPS_ANDA/install.php
```

Ikuti petunjuk di layar:
1. **Langkah 1**: Pengecekan dependensi PHP (PHP 7.4 / 8.0 / 8.1 / 8.2 / 8.3 / 8.4, ekstensi `mysqli`, `curl`, `json`, `mbstring`, `sockets`).
2. **Langkah 2**: Masukkan kredensial database MySQL (Host, Port, User, Password, Nama DB). Jika database belum ada, installer akan otomatis membuatnya.
3. **Langkah 3**: Buat akun Super Admin dan masukkan IP Publik VPS Anda.

Selesai! Anda langsung diarahkan ke Dashboard.

---

## 📋 Struktur Folder

```
API-MIKROTIK-VPN-TUNNEL/
├── ajax/
│   ├── check_router_api.php   # Endpoint async monitoring RouterOS API
│   └── test_port.php          # Endpoint async tes port socket reachability
├── config/
│   ├── config.php             # Inisialisasi session & konstanta global
│   └── database.php           # Koneksi singleton MySQLi & auto-redirect installer
├── cron/
│   └── watchdog.php           # Background cron health check tunnel & auto-expire
├── include/
│   ├── auth.php               # Otentikasi & session guard
│   ├── footer.php             # Template footer Bootstrap 5
│   ├── functions.php          # Generator port, IP pool allocator, flash helpers
│   ├── header.php             # Template navigation bar & header
│   ├── NatManager.php         # Engine DST-NAT iptables & RouterOS API
│   ├── RouterosAPI.php        # Client socket native Pure PHP untuk MikroTik
│   └── VpnManager.php         # Generator WireGuard keypair & script 1-klik MikroTik
├── pages/
│   ├── dashboard.php          # Stat cards, quick copy port, & router terbaru
│   ├── monitoring/
│   │   └── router.php         # Live CPU, memory, uptime, latency monitoring
│   ├── ports/
│   │   ├── add.php            # Form buka port forwarding baru dengan preset
│   │   └── list.php           # Daftar port forwarding publik & status
│   ├── servers/
│   │   └── list.php           # Manajemen server gateway VPS & IP Publik
│   ├── tunnels/
│   │   ├── add.php            # Form pendaftaran router tunnel baru
│   │   ├── list.php           # Daftar router tunnel terdaftar
│   │   └── script.php         # Halaman salin script 1-klik MikroTik
│   └── users/
│       └── list.php           # Manajemen pengguna & role
├── process/
│   ├── port_action.php        # Handler aksi create/delete/toggle port forwarding
│   ├── server_action.php      # Handler aksi create/delete gateway server
│   ├── tunnel_action.php      # Handler aksi create/delete router tunnel
│   └── user_action.php        # Handler aksi create/delete user
├── index.php                  # Master Router aplikasi
├── install.php                # Wizard web installer 3 langkah
├── login.php                  # Halaman login
├── logout.php                 # Halaman logout
└── README.md                  # Dokumentasi proyek
```

---

## ⚙️ Background Watchdog (Crontab)

Agar sistem otomatis mengecek apakah router online/offline dan mendeteksi tunnel yang kadaluarsa, pasang baris cron berikut di aaPanel / Linux crontab:

```bash
* * * * * php /www/wwwroot/vpn-tunnel/cron/watchdog.php > /dev/null 2>&1
```

---

## 🔒 Lisensi & Pengembang

Dikembangkan oleh **Mushawir Odegoa** & Komunitas Open Source MikroTik.
Dilisensikan di bawah lisensi MIT. Bebas digunakan untuk pengelolaan jaringan ISP, RT/RW Net, dan jaringan instansi/kantor.

