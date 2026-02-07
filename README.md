# TookTook - Sistem Rental Kamera

Aplikasi backend untuk platform penyewaan kamera yang memungkinkan pengguna menyewa berbagai jenis kamera dan admin mengelola inventori serta monitoring transaksi.

## 📋 Daftar Isi
- [Teknologi](#-teknologi)
- [Fitur Utama](#-fitur-utama)
- [Alur Sistem](#-alur-sistem)
- [Setup & Instalasi](#-setup--instalasi)
- [Struktur Database](#-struktur-database)
- [API Endpoints](#-api-endpoints)

## 🔧 Teknologi

- **Framework:** Laravel 12
- **Database:** MySQL
- **Authentication:** Laravel Sanctum
- **Testing:** PHPUnit
- **Task Queue:** Laravel Queue
- **Build Tool:** Vite

## ✨ Fitur Utama

### Fitur Admin
- 👤 Manajemen profil admin
- 📷 CRUD (Create, Read, Update, Delete) kamera
- 📊 Monitoring seluruh rental dan pembayaran
- ✅ Approval/Reject rental request
- 🔄 Proses pengembalian kamera
- 💰 Tracking status pembayaran

### Fitur User
- 📝 Registrasi & Login
- 👁️ Melihat katalog kamera yang tersedia
- 📦 Membuat rental request
- 💳 Melakukan pembayaran
- 📱 Tracking status rental
- 🔔 Notifikasi transaksi

## 🔄 Alur Sistem

```
┌─────────────────────────────────────────────────────────────┐
│                      USER REGISTRATION                      │
│              (Register → Email Verification)                │
└─────────────────────┬───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│                    USER LOGIN                               │
│           (Login → Sanctum Token Generated)                 │
└─────────────────────┬───────────────────────────────────────┘
                      │
        ┌─────────────┴─────────────┐
        │                           │
┌───────▼─────┐          ┌──────────▼──────┐
│   ADMIN     │          │      USER       │
└───────┬─────┘          └──────────┬──────┘
        │                           │
    ┌───▼────────┐         ┌────────▼────────────┐
    │ Manage     │         │ Browse Cameras     │
    │ Cameras    │         │ (Lihat katalog)    │
    └────────────┘         └────────┬───────────┘
                                    │
                         ┌──────────▼──────────┐
                         │ Create Rental      │
                         │ (Request Sewa)     │
                         └──────────┬──────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
         ┌──────────▼──────────┐        ┌──────────▼──────────┐
         │   ADMIN APPROVAL   │        │   REJECTED         │
         │  (Approve/Reject)  │        │  (Sewa Ditolak)    │
         └──────────┬──────────┘        └────────────────────┘
                    │
         ┌──────────▼──────────────┐
         │ PAYMENT PROCESSING      │
         │ (User melakukan bayar)  │
         └──────────┬──────────────┘
                    │
         ┌──────────▼──────────┐
         │  RENTAL ACTIVE      │
         │  (Camera disewakan)  │
         └──────────┬──────────┘
                    │
         ┌──────────▼──────────┐
         │  RETURN PROCESS     │
         │ (Admin proses retur)│
         └──────────┬──────────┘
                    │
         ┌──────────▼──────────┐
         │  RENTAL COMPLETED   │
         │  (Selesai disewakan)│
         └─────────────────────┘
```

## 🗄️ Struktur Database

### Tabel Users
- `id` - Primary Key
- `name` - Nama pengguna
- `email` - Email (unique)
- `phone` - Nomor telepon
- `password` - Password (hashed)
- `role` - admin/user
- `address` - Alamat
- `id_card` - Nomor identitas
- `timestamps` - created_at, updated_at

### Tabel Cameras
- `id` - Primary Key
- `name` - Nama kamera
- `description` - Deskripsi
- `daily_rate` - Harga sewakan per hari
- `brand` - Merek kamera
- `specs` - Spesifikasi teknis
- `total_units` - Total unit ketersediaan
- `available_units` - Unit yang tersedia
- `timestamps` - created_at, updated_at

### Tabel Rentals
- `id` - Primary Key
- `user_id` - FK ke users
- `camera_id` - FK ke cameras
- `start_date` - Tanggal mulai sewa
- `due_date` - Tanggal deadline pengembalian
- `returned_at` - Tanggal pengembalian aktual
- `status` - pending/approved/rejected/active/completed
- `total_price` - Harga total
- `timestamps` - created_at, updated_at

### Tabel Payments
- `id` - Primary Key
- `rental_id` - FK ke rentals
- `amount` - Jumlah pembayaran
- `payment_method` - transfer/card/cash
- `status` - pending/paid/failed
- `transaction_date` - Tanggal transaksi
- `timestamps` - created_at, updated_at

### Tabel Notifications
- `id` - Primary Key
- `user_id` - FK ke users
- `title` - Judul notifikasi
- `message` - Isi pesan
- `type` - rental/payment/system
- `read_at` - Waktu dibaca
- `timestamps` - created_at, updated_at

## 📡 API Endpoints

### Authentication
```
POST   /api/register              - Registrasi user baru
POST   /api/login                 - Login
POST   /api/logout                - Logout (require auth)
```

### Admin - Profile
```
GET    /api/admin/profile         - Lihat profil admin
```

### Admin - Camera Management
```
GET    /api/admin/camera          - Daftar semua kamera
POST   /api/admin/camera          - Tambah kamera baru
PUT    /api/admin/camera/{camera}     - Update kamera
DELETE /api/admin/camera/{camera}     - Hapus kamera
```

### Admin - Rental Monitoring
```
GET    /api/admin/rentals         - Daftar semua rental
GET    /api/admin/rentals/{rental}    - Detail rental
POST   /api/admin/rentals/{rental}/approve - Approve rental
POST   /api/admin/rentals/{rental}/reject  - Reject rental
POST   /api/admin/rentals/{rental}/return  - Proses pengembalian
```

### Admin - Payment Monitoring
```
GET    /api/admin/payments        - Daftar semua pembayaran
GET    /api/admin/payments/{payment}   - Detail pembayaran
```

### User - Profile
```
GET    /api/profile               - Lihat profil user
```

### User - Catalog
```
GET    /api/all/camera            - Lihat semua kamera tersedia
GET    /api/count/camera          - Hitung jumlah kamera
```

### User - Rental
```
POST   /api/rentals               - Buat rental request
GET    /api/rentals               - Daftar rental user
GET    /api/rentals/{rental}          - Detail rental
```

### User - Payment
```
POST   /api/payments              - Buat pembayaran baru
GET    /api/payments              - Daftar pembayaran user
GET    /api/payments/{payment}    - Detail pembayaran
PUT    /api/payments/{payment}/pay - Proses pembayaran
```

## 📦 Setup & Instalasi

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & npm
- MySQL

### Langkah-langkah

1. **Clone Repository**
```bash
# Sesuaikan dengan path project anda
cd backend-tooktook
```

2. **Install Dependencies**
```bash
npm run setup
```

Command ini akan:
- Install composer dependencies
- Copy .env.example ke .env
- Generate application key
- Run migrations
- Install npm packages
- Build assets

3. **Development Mode**
```bash
npm run dev
```

Ini akan menjalankan:
- Laravel Development Server
- Queue Listener
- Application Logs (Pail)
- Vite Build Process

4. **Running Tests**
```bash
npm run test
```

## 🔐 Authentication & Authorization

Sistem menggunakan Laravel Sanctum dengan role-based access control:

- **Role Admin:** Manajemen kamera, monitoring rental/payment
- **Role User:** Browse kamera, membuat rental, pembayaran

Middleware `role:admin` dan `role:user` melindungi setiap endpoint sesuai dengan peran pengguna.

## 📝 Catatan Penting

- Setiap rental harus di-approve oleh admin sebelum user bisa melakukan pembayaran
- Kamera hanya bisa disewakan jika tersedia (available_units > 0)
- Payment harus dikonfirmasi sebelum status rental berubah menjadi active
- Notifikasi otomatis dikirim saat ada perubahan status rental/payment

---

**Developed with ❤️**
