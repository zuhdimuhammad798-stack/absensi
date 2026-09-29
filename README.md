# NEXORA — Presensi QR Kampus

Aplikasi presensi kelas berbasis QR Code (PHP + MySQL). Setiap mahasiswa
punya "kartu masuk" QR pribadi; dosen membuat sesi kelas lalu memindai QR
tiap mahasiswa untuk mencatat kehadiran secara real-time — status
**Hadir / Telat** dihitung otomatis dari jam mulai kelas dan toleransi
yang ditentukan.

Proyek ini adalah desain ulang penuh dari versi sebelumnya: struktur kode
dirapikan, semua endpoint backend yang sebelumnya belum ada dilengkapi,
dan tampilan dibangun ulang dengan identitas visual baru bertema
"boarding pass akademik" — sesi presensi divisualisasikan seperti tiket
sah, kartu QR mahasiswa seperti kartu masuk pribadi.

## Fitur

- Login dosen/admin, password di-hash dengan `password_hash` (bcrypt)
- Pengaturan akun untuk memperbarui nama tampilan dan mengganti password dengan verifikasi password saat ini
- Sidebar navigasi tetap + tampilan mobile responsif dengan drawer menu
- Kelola data mahasiswa (tambah, hapus, cari) + QR Code unik otomatis
- Kartu QR per mahasiswa yang bisa diunduh sebagai PNG
- Buat sesi presensi (mata kuliah, kelas, jam, toleransi telat) dengan
  tampilan "tiket sesi" yang menonjolkan status aktif
- Scan QR via kamera (HP & laptop) — status Hadir/Telat otomatis,
  disertai riwayat scan langsung di layar
- Dashboard real-time (polling AJAX setiap 4 detik) dengan pencarian
  roster dan highlight baris saat status berubah
- Notifikasi toast untuk setiap aksi (tambah/hapus mahasiswa, buka/tutup
  sesi) menggantikan reload polos
- Laporan per sesi + export CSV (UTF-8, siap dibuka di Excel)
- Seluruh query database memakai PDO **prepared statements**

## Struktur Folder

```
HADIRQ/
├── api/                     # Endpoint backend (form actions & AJAX JSON)
│   ├── absen.php            # Catat kehadiran dari hasil scan QR
│   ├── buat_sesi.php        # Buat sesi presensi baru
│   ├── tutup_sesi.php       # Tutup sesi aktif
│   ├── get_roster.php       # JSON roster untuk polling dashboard
│   ├── tambah_mahasiswa.php
│   ├── hapus_mahasiswa.php
│   └── export_csv.php
├── assets/
│   ├── css/style.css        # Design system NEXORA
│   └── js/, img/
├── config/
│   ├── koneksi.php          # Koneksi PDO ke MySQL
│   └── auth.php             # Session, login guard, flash message
├── includes/
│   ├── head.php             # <head> bersama
│   ├── sidebar.php          # Navigasi sidebar + mobile bar
│   └── toast.php            # Notifikasi toast dari flash session
├── database/
│   ├── nexora_db.sql        # Skema + contoh data mahasiswa
│   └── seed_admin.php       # Pembuat akun admin default (jalankan 1x)
├── kartu_cetak.php          # Cetak semua kartu QR satu kelas
├── dashboard.php
├── login.php / logout.php
├── mahasiswa.php
├── akun.php                  # Pengaturan nama tampilan dan password
├── qr_view.php
├── scan.php
├── laporan.php
└── index.php
```

## Instalasi (XAMPP / Laragon / server PHP+MySQL apa saja)

1. **Salin folder** `HADIRQ` ke direktori web server, misalnya
   `C:\xampp\htdocs\HADIRQ` atau `/var/www/html/HADIRQ`.

2. **Buat database** — impor skema lewat phpMyAdmin atau CLI:

   ```
   database/nexora_db.sql
   ```

   Ini membuat database `nexora_db`, seluruh tabel, dan beberapa contoh
   data mahasiswa.

3. **Akun admin otomatis dibuat** saat pertama kali membuka halaman login
   (`admin` / `admin123`) — tidak perlu menjalankan seeder. `database/seed_admin.php`
   tetap ada sebagai opsi manual. **Ganti password setelah login pertama.**

4. **Atur koneksi database** di `config/koneksi.php` bila perlu (default:
   host `localhost`, user `root`, password kosong — sesuai default
   XAMPP).

5. **Pasang pustaka pemindai QR** dari folder `HADIRQ`:

```
npm install
```

Ini membuat scan kamera tetap tersedia tanpa mengandalkan CDN.

6. **Akses aplikasi**:

   ```
   http://localhost/HADIRQ/
   ```

7. **Login** dengan `admin` / `admin123`, lalu buka **Pengaturan Akun**
   untuk mengganti nama tampilan dan password awal.

## Alur Penggunaan

1. **Kelola Mahasiswa** → tambahkan mahasiswa baru. QR Code unik otomatis
   dibuat. Klik **Lihat QR** untuk melihat/mengunduh kartu QR tiap
   mahasiswa (bisa dicetak atau dikirim ke mahasiswa masing-masing).
2. Di **Dashboard**, buat sesi presensi baru (kelas, mata kuliah, jam
   mulai/selesai, toleransi telat).
3. Klik **Mulai Scan** → arahkan kamera ke QR Code tiap mahasiswa. Status
   kehadiran & waktu absen langsung tercatat, dan tabel di Dashboard
   ter-update otomatis lewat polling.
4. Buka **Laporan** untuk melihat rekap per sesi dan **Export CSV**.

## Catatan Teknis

- QR Code dibuat & dipindai sepenuhnya di sisi browser dengan library
  JavaScript open-source `qrcodejs` (generate) dan `html5-qrcode`
  (scan) — tidak perlu ekstensi PHP tambahan. Scanner dimuat lokal dari
  `node_modules` setelah `npm install`, dengan CDN sebagai cadangan.
- Kamera memerlukan koneksi **HTTPS** atau `localhost` agar browser
  mengizinkan akses (`getUserMedia`).
- Status Hadir/Telat dihitung dari `jam_mulai` sesi + `batas_telat`
  (menit) dibandingkan waktu server saat scan — pastikan jam server
  sudah sesuai zona waktu setempat (`date.timezone` di `php.ini`).
- Untuk produksi: aktifkan HTTPS, ganti kredensial database, hapus
  `database/seed_admin.php`, dan pertimbangkan rate-limiting pada
  `api/absen.php`.

## Keamanan

- Semua password disimpan dengan `password_hash()` / diverifikasi
  dengan `password_verify()`.
- Semua query database memakai PDO prepared statements.
- Session ID diregenerasi saat login untuk mencegah session fixation.
- Ganti password awal melalui menu **Pengaturan Akun**. Aplikasi meminta
  password saat ini, mewajibkan password baru minimal 8 karakter, dan
  melindungi pengiriman formulir dengan token CSRF.

## Perubahan v2

- Folder diganti nama menjadi `HADIRQ` (nama tampilan aplikasi tetap NEXORA).
- **QR kini lokal**: `assets/js/qrcode.js` (encoder MIT) — tidak lagi bergantung CDN.
- **Scan berlapis**: BarcodeDetector bawaan browser → html5-qrcode (lokal/CDN cadangan)
  → input manual (NIM atau alat scan barcode USB).
- Zona waktu WIB (`APP_TZ` di `config/koneksi.php`); ubah bila perlu.
- Cetak kartu QR satu kelas: Kelola Mahasiswa → _Cetak Kartu Kelas X_.
- Perbaikan XSS di riwayat scan & redirect saat sesi login habis.

## Kamera & HTTPS

Kamera hanya jalan di `https://` atau `http://localhost`. Jika HP membuka lewat IP LAN
(`http://192.168.x.x`), kamera akan diblokir browser — gunakan HTTPS (mis. Cloudflare Tunnel/ngrok,
atau Laragon dengan SSL) atau pakai input manual. `npm install` menyediakan pustaka lokal untuk
browser tanpa BarcodeDetector (mis. Safari/Firefox), sehingga pemindaian tidak memerlukan internet.
