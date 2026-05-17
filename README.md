# 📚 TugasKu — Panduan Setup Lengkap

Owner = 71 Project 

---

## 🗂️ Struktur File

```
tugasku/
├── index.php          ← Halaman utama (dashboard)
├── auth.php           ← Callback login Google
├── logout.php         ← Halaman logout
├── api.php            ← Backend AJAX (add/delete/upload tugas)
├── config.php         ← ⚙️ KONFIGURASI (isi Client ID & Secret di sini)
├── includes/
│   ├── google.php     ← Helper Google OAuth & Drive API
│   └── tasks.php      ← Helper manajemen data tugas
└── data/
    └── tasks.json     ← Database tugas (otomatis dibuat)
```

---

## 🚀 Cara Setup (Step by Step)

### LANGKAH 1 — Buat Google Cloud Project (GRATIS)

1. Buka https://console.cloud.google.com
2. Login dengan akun Google kamu
3. Klik tombol **"Select a project"** di pojok kiri atas
4. Klik **"New Project"**
5. Isi nama project: `TugasKu` → klik **Create**
6. Tunggu beberapa detik, lalu pastikan project `TugasKu` sudah terpilih

---

### LANGKAH 2 — Aktifkan Google Drive API

1. Di sidebar kiri, klik **"APIs & Services"** → **"Library"**
2. Search: `Google Drive API`
3. Klik hasilnya → klik tombol **"Enable"**
4. Tunggu sampai halaman berubah (sudah aktif)

---

### LANGKAH 3 — Buat OAuth Consent Screen

1. Klik **"APIs & Services"** → **"OAuth consent screen"**
2. Pilih **"External"** → klik **Create**
3. Isi form:
   - **App name**: `TugasKu`
   - **User support email**: email kamu sendiri
   - **Developer contact**: email kamu sendiri
4. Klik **Save and Continue**
5. Di halaman **Scopes** → langsung klik **Save and Continue** (skip)
6. Di halaman **Test users** → klik **"+ ADD USERS"** → masukkan email Google kamu sendiri → klik **Save**
7. Klik **Save and Continue** → selesai

---

### LANGKAH 4 — Buat OAuth Credentials

1. Klik **"APIs & Services"** → **"Credentials"**
2. Klik **"+ Create Credentials"** → pilih **"OAuth client ID"**
3. Pilih **Application type**: `Web application`
4. Isi **Name**: `TugasKu Web`
5. Di bagian **Authorized redirect URIs** → klik **"+ ADD URI"**
6. Isi URI sesuai lokasi file kamu:
   - Jika pakai XAMPP lokal: `http://localhost/tugasku/auth.php`
   - Jika pakai server online: `https://namadomain.com/tugasku/auth.php`
7. Klik **Create**
8. **SIMPAN** Client ID dan Client Secret yang muncul (copy ke notepad!)

---

### LANGKAH 5 — Isi config.php

Buka file `config.php`, ganti bagian ini:

```php
define('GOOGLE_CLIENT_ID',     'GANTI_DENGAN_CLIENT_ID_KAMU');
define('GOOGLE_CLIENT_SECRET', 'GANTI_DENGAN_CLIENT_SECRET_KAMU');
define('GOOGLE_REDIRECT_URI',  'http://localhost/tugasku/auth.php');
```

Contoh setelah diisi:

```php
define('GOOGLE_CLIENT_ID',     '123456789-abcdefg.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-xxxxxxxxxxxxxx');
define('GOOGLE_REDIRECT_URI',  'http://localhost/tugasku/auth.php');
```

Juga sesuaikan **timezone** kamu (sudah diset ke WITA/Makassar):
```php
date_default_timezone_set('Asia/Makassar'); // WITA ✅
// date_default_timezone_set('Asia/Jakarta'); // WIB
// date_default_timezone_set('Asia/Jayapura'); // WIT
```

---

### LANGKAH 6 — Pasang di XAMPP / Server

**Jika pakai XAMPP (Windows/Mac/Linux):**

1. Copy folder `tugasku/` ke dalam folder `htdocs/` di XAMPP
   - Windows: `C:\xampp\htdocs\tugasku\`
   - Mac: `/Applications/XAMPP/htdocs/tugasku/`
2. Pastikan Apache sudah Running di XAMPP Control Panel
3. Buka browser → akses: `http://localhost/tugasku`

**Jika pakai server hosting:**
1. Upload semua file ke folder `public_html/tugasku/` via cPanel/FTP
2. Pastikan PHP versi 7.4+ dan ekstensi `curl` aktif

---

### LANGKAH 7 — Jalankan!

1. Buka `http://localhost/tugasku` di browser
2. Klik **"Masuk dengan Google"**
3. Login dengan akun Google yang sudah ditambahkan sebagai Test User (Langkah 3)
4. Izinkan akses ke Google Drive → kamu akan diarahkan ke dashboard
5. ✅ Siap digunakan!

---

## 🔥 Cara Pakai

### Tambah Tugas Baru
- Klik tombol **"+ Tambah Tugas"**
- Isi judul, mata pelajaran, deadline, dan prioritas
- Klik **"Tambah Tugas"**

### Kumpulkan Tugas
- Klik tombol **"📤 Kumpulkan"** pada tugas yang ingin dikumpulkan
- Pilih atau drag & drop file tugas kamu
- Klik **"🚀 Upload ke Drive"**
- File akan otomatis tersimpan di Google Drive kamu dalam folder **"TugasKu - Pengumpulan Tugas"**

### Streak & Motivasi
- Setiap tugas yang dikumpulkan **sebelum deadline** = streak +1 🔥
- Jika terlambat = streak kembali ke 0
- Semakin panjang streak, semakin terasa "sayang untuk diputus"!

---

## ❓ Troubleshooting

### "Error 400: redirect_uri_mismatch"
→ URI di Google Console tidak sama persis dengan yang di `config.php`.
Pastikan tidak ada `/` di akhir, dan protocol-nya benar (http vs https).

### "Access blocked: TugasKu has not completed the Google verification process"
→ Kamu belum menambahkan email kamu sebagai Test User di Langkah 3.
Buka kembali OAuth Consent Screen → Test Users → tambahkan email kamu.

### File gagal diupload
→ Pastikan ekstensi PHP `curl` aktif.
Di XAMPP: buka `php.ini`, cari `;extension=curl`, hapus titik koma di depannya → restart Apache.

### Halaman putih / error
→ Aktifkan error reporting: tambahkan di baris pertama `index.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

## 🔒 Keamanan & Privasi

- File tugas tersimpan di **Google Drive milikmu sendiri**, bukan server orang lain
- Data tugas (judul, deadline, dll) disimpan lokal di file `data/tasks.json`
- Token Google disimpan di PHP Session (hilang saat browser ditutup)
- Aplikasi ini **tidak menyimpan password** kamu — login via Google OAuth

---

Dibuat dengan batagor depan adel 3 dan teh pucuk harum 1 botol.
