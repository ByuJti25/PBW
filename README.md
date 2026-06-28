# Platform Lelang Online Real-time (LelangAPP)

Aplikasi web fullstack platform lelang daring yang berjalan secara real-time. Dibangun menggunakan arsitektur **Single Page Application (SPA)** dengan frontend **Vue 3** terpisah dan mengonsumsi REST API **Laravel 11/12**. Sinkronisasi harga penawaran dan status lelang berjalan secara instan tanpa memuat ulang halaman berkat integrasi **Laravel Reverb (WebSockets)**.

---

## 📋 Prasyarat Sistem (Prerequisites)

Sebelum menjalankan aplikasi ini, pastikan perangkat Anda telah terinstal:
* **PHP** versi 8.2 atau lebih tinggi
* **Composer** (untuk dependensi Laravel)
* **Node.js** versi 18 atau lebih tinggi & **NPM** (untuk dependensi Vue)
* **MySQL** atau database relational sejenis (bisa diakses via TablePlus / phpMyAdmin)

---

## 🛠️ Langkah Instalasi (Installation Steps)

### 1. Kloning / Unduh Proyek
Ekstrak atau buka folder utama proyek `/Lelang_APP` di terminal Anda.

### 2. Setup Backend (Laravel)
Masuk ke direktori backend:
```bash
cd backend
```

Instal dependensi PHP menggunakan Composer:
```bash
composer install
```

Salin file konfigurasi `.env` dari contoh template:
```bash
cp .env.example .env
```

Buat Application Key Laravel:
```bash
php artisan key:generate
```

Sesuaikan konfigurasi database Anda di file `.env` (baca bagian konfigurasi di bawah). Setelah itu, jalankan migrasi database beserta pembuatan data uji (*seeder*):
```bash
php artisan migrate:fresh --seed
```

---

### 3. Setup Frontend (Vue 3)
Kembali ke root lalu masuk ke direktori frontend:
```bash
cd ../frontend
```

Instal dependensi Node/NPM:
```bash
npm install
```

---

## ⚙️ Konfigurasi File `.env`

### A. Konfigurasi Backend (`backend/.env`)
Pastikan konfigurasi koneksi database dan Reverb diisi dengan benar:

```ini
# Database Connection
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lelang_app
DB_USERNAME=root
DB_PASSWORD=

# Broadcast Driver
BROADCAST_CONNECTION=reverb

# Laravel Reverb Config
REVERB_APP_ID=804562
REVERB_APP_KEY=lelangappkey123
REVERB_APP_SECRET=lelangappsecret456
REVERB_HOST="127.0.0.1"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### B. Konfigurasi Frontend (`frontend/.env`)
Pastikan file `.env` di dalam folder frontend berisi variabel penunjuk ke API server Laravel dan Reverb:

```ini
VITE_API_URL=http://localhost:8000/api
VITE_REVERB_APP_KEY=lelangappkey123
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

---

## 🚀 Cara Menjalankan Layanan Aplikasi

Untuk menjalankan aplikasi secara utuh di laptop Anda, buka terminal terpisah untuk menjalankan perintah-perintah berikut secara bersamaan:

### 1. Jalankan Server API Backend (Laravel)
Jalankan server HTTP lokal Laravel (port default: 8000):
```bash
cd backend
php artisan serve
```

### 2. Jalankan Server WebSocket (Laravel Reverb)
Aktifkan server penanganan koneksi real-time:
```bash
cd backend
php artisan reverb:start
```

### 3. Jalankan Task Scheduler
Digunakan untuk memantau waktu mulai/berakhirnya lelang dan mengubah statusnya secara otomatis setiap menit:
```bash
cd backend
php artisan schedule:work
```

### 4. Jalankan Worker Queue (Opsional)
Digunakan jika sistem pengiriman notifikasi/event dimasukkan ke dalam antrean (queue database):
```bash
cd backend
php artisan queue:work
```

### 5. Jalankan Server Frontend (Vue 3)
Jalankan dev server frontend Vue (port default: 5173):
```bash
cd frontend
npm run dev
```

Buka browser Anda dan akses halaman web di alamat: **`http://localhost:5173`**

---

## 👥 Akun Demo Hasil Seeder (Demo Accounts)

Setelah Anda menjalankan perintah `php artisan migrate:fresh --seed`, database akan otomatis terisi dengan 2 akun uji coba berikut untuk mempermudah pengujian:

### 1. Akun Penjual (Seller)
* **Nama**: Inoatam
* **Email**: `inoatam@gmail.com`
* **Password**: `inoatam123`
* **Peran**: Membuat lelang baru, mengunggah foto, dan memantau penawaran masuk di Dashboard Penjual.

### 2. Akun Penawar (Bidder)
* **Nama**: Atik
* **Email**: `atikgeulia@gmail.com`
* **Password**: `atik12345`
* **Peran**: Menjelajahi halaman utama lelang dan melakukan penawaran harga (*bidding*) secara real-time.
