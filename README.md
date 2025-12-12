# Detik Test - Laravel API

Aplikasi Laravel untuk manajemen produk dengan fitur import CSV dan autentikasi menggunakan Laravel Sanctum.

## Persyaratan Sistem

- PHP >= 8.2
- Composer
- PostgreSQL >= 12
- Git

## Cara Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/FahriAlfianNur/be-detik.git
cd be-detik
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Konfigurasi Environment

Copy file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

### 4. Konfigurasi Database PostgreSQL

Edit file `.env` dan sesuaikan konfigurasi database PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nama_database
DB_USERNAME=username_postgresql
DB_PASSWORD=password_postgresql
```

**Catatan:** Pastikan Anda sudah membuat database di PostgreSQL sebelum melanjutkan.

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Jalankan Migration

```bash
php artisan migrate
```

### 7. Jalankan Queue Worker (Terminal Terpisah)

Aplikasi ini menggunakan queue untuk memproses import CSV. Jalankan worker di terminal terpisah:

```bash
php artisan queue:work
```

### 8. Jalankan Development Server

```bash
php artisan serve
```

Aplikasi akan berjalan di `http://localhost:8000`

## Dokumentasi API

Base URL: `http://localhost:8000/api`

### 1. Register User

Endpoint untuk mendaftarkan user baru sebelum menggunakan aplikasi.

**Endpoint:** `POST /api/register`

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response Success (201):**
```json
{
    "message": "User registered successfully",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2025-12-12T07:11:49.000000Z",
        "updated_at": "2025-12-12T07:11:49.000000Z"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890"
}
```

**Contoh Request (cURL):**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

---

### 2. Login

Endpoint untuk login dan mendapatkan token autentikasi.

**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response Success (200):**
```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2025-12-12T07:11:49.000000Z",
        "updated_at": "2025-12-12T07:11:49.000000Z"
    },
    "token": "2|abcdefghijklmnopqrstuvwxyz1234567890"
}
```

**Response Error (401):**
```json
{
    "message": "Invalid credentials"
}
```

**Contoh Request (cURL):**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

---

### 3. Get User Profile

Endpoint untuk mendapatkan informasi user yang sedang login.

**Endpoint:** `GET /api/user`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2025-12-12T07:11:49.000000Z",
    "updated_at": "2025-12-12T07:11:49.000000Z"
}
```

**Contoh Request (cURL):**
```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Accept: application/json"
```

---

### 4. Import Products (CSV)

Endpoint untuk mengupload file CSV dan mengimport produk ke database.

**Endpoint:** `POST /api/import`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
Accept: application/json
```

**Request Body (Form Data):**
- `file`: File CSV (required)

**Format CSV:**
```csv
name,sku,price,stock
Product 1,SKU0001,10000,100
Product 2,SKU0002,20000,50
```

**Response Success (200):**
```json
{
    "message": "Import job has been queued",
    "job_id": "abc123-def456-ghi789",
    "status": "pending"
}
```

**Response Error (422):**
```json
{
    "message": "The file field is required.",
    "errors": {
        "file": [
            "The file field is required."
        ]
    }
}
```

**Contoh Request (cURL):**
```bash
curl -X POST http://localhost:8000/api/import \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Accept: application/json" \
  -F "file=@/path/to/your/products.csv"
```

---

### 5. Check Import Status

Endpoint untuk mengecek status proses import berdasarkan job ID.

**Endpoint:** `GET /api/import/status/{id}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
    "job_id": "abc123-def456-ghi789",
    "status": "completed",
    "total": 100,
    "success": 95,
    "failed": 5,
    "created_at": "2025-12-11T18:00:25.000000Z",
    "updated_at": "2025-12-11T18:00:50.000000Z"
}
```

**Status yang mungkin:**
- `pending`: Import sedang menunggu diproses
- `in_progress`: Import sedang diproses
- `completed`: Import selesai
- `failed`: Import gagal

**Contoh Request (cURL):**
```bash
curl -X GET http://localhost:8000/api/import/status/abc123-def456-ghi789 \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Accept: application/json"
```

---

### 6. Logout

Endpoint untuk logout dan menghapus token autentikasi.

**Endpoint:** `POST /api/logout`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
    "message": "Logged out successfully"
}
```

**Contoh Request (cURL):**
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Accept: application/json"
```

---

## Catatan

1. **Autentikasi**: Semua endpoint kecuali `/register` dan `/login` memerlukan token autentikasi di header `Authorization: Bearer {token}`
2. **Queue Worker**: Pastikan queue worker berjalan untuk memproses import CSV
3. **Database**: Aplikasi ini menggunakan PostgreSQL sebagai database
4. **Token**: Simpan token yang didapat dari endpoint register/login untuk digunakan di request selanjutnya

## Troubleshooting

### Queue tidak berjalan
Pastikan queue worker sudah dijalankan dengan perintah:
```bash
php artisan queue:work
```

### Error koneksi database
Periksa konfigurasi database di file `.env` dan pastikan PostgreSQL sudah berjalan.

### Token tidak valid
Pastikan token yang digunakan masih valid dan belum dihapus (logout).
