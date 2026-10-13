# Ecolife — Edukasi dan Pemantauan Kebiasaan Konsumsi Ramah Lingkungan

Versi dengan **login wajib**, dashboard pengguna, panel admin, dan desain *Nature Glassmorphism*.
Teknologi: **PHP 7.4+ (native, tanpa framework) + MySQL/MariaDB (PDO)**. Tanpa library JS/CSS tambahan.

> Proyek awal hanya berupa satu file `index.html` (data di localStorage, tanpa server). Karena belum ada backend,
> backend PHP + MySQL ditambahkan. Semua fitur lama dipertahankan (lihat "Catatan perubahan").

## 1. Menjalankan (XAMPP / Laragon)

1. Salin folder `ecolife/` ke `C:\xampp\htdocs\ecolife` (atau `www/` di Laragon). Start **Apache** dan **MySQL**.
2. Buat database: buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/schema.sql` → **Import**.
   (atau terminal: `mysql -u root -p < database/schema.sql`)
3. Pengaturan koneksi. Bawaan: host `127.0.0.1`, database `ecolife`, user `root`, password kosong (cocok untuk XAMPP).
   Jika berbeda: salin `includes/config.local.sample.php` → `includes/config.local.php` lalu ubah isinya
   (atau pakai env `ECOLIFE_DB_HOST`, `ECOLIFE_DB_NAME`, `ECOLIFE_DB_USER`, `ECOLIFE_DB_PASS`).
   Untuk server publik, buat user MySQL khusus (bukan `root`) dan password kuat.
4. Buat akun admin (tidak ada akun/password bawaan):
   ```
   C:\xampp\php\php.exe database\create_admin.php
   ```
   Atau daftar biasa lewat website, lalu: `php database/create_admin.php --promote email@anda.com`.
5. Buka `http://localhost/ecolife/`.

Zona waktu bawaan `Asia/Makassar` (WITA); ubah di `config.local.php` (`timezone`). Jika muncul error koneksi, atur `'debug' => true` sementara.

## 2. Struktur

```
index.php            Landing page (publik)
login.php register.php logout.php
dashboard.php        Dashboard pengguna      (wajib login)
pantau.php           Pantau kebiasaan        (wajib login)
prediksi.php         Prediksi                (wajib login)
profil.php           Profil + ganti password (wajib login)
admin/index.php      Kelola pengguna         (wajib admin)
api/pantau.php       API poin/riwayat        (login + CSRF)
api/predict.php      API prediksi            (login + CSRF)
includes/            bootstrap (sesi, CSRF, guard), auth, layout, habits, predict, model.json  (diblokir dari web)
database/            schema.sql, create_admin.php                                              (diblokir dari web)
assets/css/app.css   assets/js/*.js   assets/img/*
```

## 3. Cara menguji

| Skenario | Langkah | Hasil yang benar |
|---|---|---|
| Pengunjung | Buka `/`, klik **Pantau** atau **Prediksi** | Diarahkan ke login dengan pesan; setelah login kembali ke halaman tujuan |
| Akses langsung | Tanpa login buka `/pantau.php`, `/prediksi.php`, `/dashboard.php`, `/api/pantau.php`, `/admin/index.php` | Redirect ke login (API: JSON 401) |
| Registrasi | `/register.php` — isi nama, email, password (≥8, huruf+angka) | Akun dibuat, langsung masuk ke Dashboard |
| Login salah | Password keliru | "Email atau password salah." (5x gagal → dikunci 15 menit) |
| Pantau | Centang kebiasaan → **Simpan hari ini** | Poin bertambah, riwayat muncul. Mencentang kebiasaan yang sama lagi di hari yang sama ditolak (poin tidak bertambah) |
| Prediksi | Isi form → **Hitung prediksi** | Panel hasil + riwayat bertambah |
| Data terpisah | Buat 2 akun | Poin/riwayat masing-masing terpisah |
| Logout | Menu profil → **Keluar**, lalu tombol Back / buka URL terlindungi | Tetap diminta login |
| Pengguna biasa → admin | Login sebagai user, buka `/admin/index.php` | 403 "Akses ditolak" |
| Admin | Login sebagai admin → menu profil → **Admin** | Daftar pengguna, cari, ubah role, hapus akun |

## 4. Keamanan yang diterapkan

- `password_hash()` / `password_verify()`; sesi `HttpOnly`, `SameSite=Lax`, `Secure` otomatis di HTTPS, ID sesi diganti saat login, timeout 2 jam tidak aktif.
- Semua query memakai *prepared statements* (PDO, `EMULATE_PREPARES=false`).
- Token CSRF pada semua form dan API yang mengubah data; logout hanya lewat POST.
- Role dibaca dari database di setiap request (bukan dari sesi/form). Admin tidak bisa mengubah/menghapus dirinya sendiri.
- Poin dihitung di server dari katalog kebiasaan; `UNIQUE (user, tanggal, kebiasaan)` + transaksi mencegah poin ganda.
- Pembatasan percobaan login, whitelist tujuan `next` (tanpa open redirect), output di-escape, header CSP/nosniff/no-store, `includes/` dan `database/` diblokir lewat `.htaccess`.
- Jika memakai **Nginx**, blokir akses ke `/includes/` dan `/database/` secara manual.

## 5. Catatan perubahan terhadap versi lama

- **Prediksi dihitung di server** (`includes/predict.php`) dengan bobot yang sama (`includes/model.json`), agar fitur benar-benar hanya untuk pengguna login. Hasil identik dengan versi browser (diuji 300 kasus acak, selisih < 1e-15).
- **Perbaikan bug**: opsi "Gas" pada sumber energi dulu bernilai `gas`, padahal model dilatih dengan kategori `natural gas`, sehingga pilihan itu tidak pernah berpengaruh. Kini dipetakan dengan benar. Hasil prediksi pengguna "Gas" dapat berbeda dari versi lama.
- "Hari tercatat" kini = jumlah hari berbeda yang punya catatan (sebelumnya: jumlah klik simpan).
- Riwayat pencatatan menampilkan 10 terbaru; riwayat prediksi menyimpan maksimal 50 terbaru per pengguna.
- Teks bagian "Model" diperbarui karena perhitungan kini di server (yang disimpan hanya hasil, bukan data isian).
- Font diganti ke Plus Jakarta Sans (Google Fonts, dengan fallback sistem). Perlu internet saat pertama dimuat.
- Foto latar memakai gambar daun pakis yang Anda lampirkan; ganti `assets/img/fern-*.jpg|webp` untuk foto lain.
