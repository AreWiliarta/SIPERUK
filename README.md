# SIPERUK - Sistem Informasi Peminjaman Ruangan Kampus

SIPERUK adalah sebuah Sistem Informasi berbasis Web yang dirancang untuk mengatasi permasalahan pengelolaan dan peminjaman ruangan di lingkungan kampus. Sistem ini dibangun untuk mendigitalisasi proses yang sebelumnya manual, mencegah terjadinya *double booking* jadwal, dan mempercepat alur birokrasi persetujuan penggunaan ruangan oleh pihak Sarana & Prasarana.

---

## 🌟 Fitur Utama

### 👑 Modul Admin (Sarpras)
*   **Dashboard Interaktif**: Menampilkan ringkasan metrik statistik ketersediaan dan status pengajuan peminjaman hari ini.
*   **Manajemen Ruangan (CRUD)**: Kelola data ruangan, ubah status ruangan (*Active* / *Maintenance*). Jika ruangan di-set ke *Maintenance*, seluruh pesanan yang masih tertunda di ruangan tersebut akan dibatalkan otomatis.
*   **Panel Persetujuan (Approval)**: Menerima, meninjau, menyetujui, atau menolak (*Reject* dengan wajib memberikan alasan) permohonan peminjaman ruangan.
*   **Manajemen Jadwal**: Tabel khusus untuk melihat dan mengelola seluruh jadwal yang telah terpakai. Admin dapat mengedit ruangan, jam, dan tanggal, maupun membatalkan paksa permohonan.
*   **Master Kalender (*TimeGrid Week*)**: Visualisasi ketersediaan ruangan mingguan dengan antarmuka seret & lepaskan (klik) yang terintegrasi *pop-up* SweetAlert modern untuk menampilkan detail pengguna, serta *shortcut* ke menu edit jadwal.

### 🎓 Modul Pengguna (Mahasiswa / Dosen / UKM)
*   **Katalog Ruangan**: Menampilkan seluruh ruangan yang berstatus aktif beserta foto, kapasitas, dan fasilitas (AC, Proyektor, dll).
*   **Sistem Peminjaman Otomatis (Booking)**: Formulir pengajuan ruang dengan validasi *Double-booking* (*real-time*). Pengajuan minimal H-2 sebelum hari H (diatur via UI & validasi server).
*   **Riwayat Peminjaman**: Melihat status pengajuan (*Pending*, *Approved*, *Rejected*). Jika ditolak, sistem menampilkan alasannya. Peminjam juga dapat membatalkan pengajuan saat statusnya masih *Pending*.
*   **Kalender Publik**: Melihat kotak-kotak jadwal ruangan terpakai per minggu agar pengguna dapat mencari slot kosong secara visual sebelum memesan.

---

## 💻 Teknologi yang Digunakan

*   **Backend**: PHP Native (PDO)
*   **Database**: MySQL (MariaDB)
*   **Frontend**: HTML5, Vanilla JavaScript, Vanilla CSS (*Glassmorphism Premium Design*)
*   **Library Eksternal**:
    *   [FullCalendar](https://fullcalendar.io/) (Untuk antarmuka Kalender interaktif)
    *   [SweetAlert2](https://sweetalert2.github.io/) (Untuk *pop-up* notifikasi elegan & konfirmasi formulir)
    *   Google Fonts (Inter)
*   **Keamanan**: `password_hash()` BCRYPT untuk *password*, dan validasi keamanan formulir *Prepared Statement* SQL.

---

## 📁 Struktur Direktori & Penjelasan File

Berikut adalah rincian folder dan file yang menyusun sistem SIPERUK beserta fungsi singkatnya:

### 1. Akar Proyek (Root)
*   `index.php` : Halaman awal (Gerbang Login & Pendaftaran Akun).
*   `logout.php` : Skrip untuk menghancurkan sesi (*session*) pengguna yang sedang aktif (Keluar).
*   `database.sql` : Skema struktur tabel *database* mentah (`users`, `rooms`, `bookings`).
*   `seed_dummy.php`, `seed_rooms.php`, `seed_bookings.php` : Kumpulan skrip *seeder* otomatis untuk memasukkan data uji coba/dummy (ruangan, user, jadwal) ke dalam *database*.
*   `README.md` : File dokumentasi utama dari proyek ini.

### 2. Folder `includes/` (Logika & Konfigurasi Inti)
*   `init.php` : File inisialisasi utama yang memanggil sesi (session), koneksi DB, dan memuat semua fungsi bawaan.
*   `functions.php` : File perpustakaan (*library*) fungsi khusus seperti sanitasi input, validasi keamanan, pelindung sesi (`requireLogin`), dan *helper* lainnya.

### 3. Folder `config/`
*   `db.php` *(jika ada)* : File yang menyimpan variabel rahasia untuk koneksi ke *Database* (Host, User, Password, DB Name) menggunakan antarmuka PDO.

### 4. Folder `admin/` (Modul Admin)
*   `index.php` : Halaman Dashboard Admin yang berisi statistik (Widget Jumlah Pengajuan, Ruangan Aktif, dsb).
*   `rooms.php` : Halaman manajemen (CRUD) seluruh data Master Ruangan (Tambah, Edit, Hapus, Set Maintenance).
*   `approvals.php` : Layar eksekusi keputusan Admin untuk menyetujui (Approve) atau menolak (Reject) antrean peminjaman.
*   `schedules.php` : Halaman daftar jadwal terpakai dalam bentuk tabel (List View) dengan opsi pembatalan paksa.
*   `calendar.php` : Halaman jadwal dalam wujud visual Kalender Mingguan (*TimeGrid*) dari FullCalendar.
*   `export_approvals.php` & `export_schedules.php` : Skrip *backend* untuk mengekspor data laporan ke dalam format *spreadsheet* (Excel/CSV).
*   `header.php` & `footer.php` : Komponen antarmuka (UI) bilah navigasi atas (Navbar) dan penutup bawah halaman admin.

### 5. Folder `user/` (Modul Pemohon / Mahasiswa)
*   `index.php` : Halaman Katalog Ruangan yang menampilkan *card* daftar ruangan yang siap dipesan.
*   `book.php` : Halaman dan logika Formulir Pengajuan (Pilih ruangan, isi tanggal, dan jam kegiatan).
*   `history.php` : Halaman Riwayat Saya, tempat pengguna melacak apakah pesanannya di-*pending*, disetujui, atau ditolak (beserta alasannya).
*   `calendar.php` : Layar Kalender khusus mode baca, agar pemohon bisa mengecek kekosongan ruangan sebelum mengisi formulir.
*   `header.php` & `footer.php` : Komponen antarmuka bilah navigasi khusus untuk menu sisi Pengguna.

### 6. Folder Tambahan
*   `assets/` : Menyimpan file gaya (CSS), *script* murni (JS), maupun ikon gambar bawaan *layout*.
*   `uploads/` : Folder repositori tempat gambar-gambar ruangan yang diunggah (*upload*) oleh Admin disimpan.

---

## 🚀 Panduan Instalasi & Cara Menjalankan (Lokal)

1.  **Persyaratan Lingkungan**:
    *   Server lokal seperti XAMPP, Laragon, atau PHP *built-in server* (PHP v7.4 atau v8.0+ direkomendasikan).
    *   Pastikan ekstensi `pdo_mysql` diaktifkan di dalam konfigurasi `php.ini`.

2.  **Konfigurasi Database**:
    *   Buka phpMyAdmin atau *client* database Anda.
    *   Buat sebuah *database* baru (misalnya dengan nama `siperuk`).
    *   Impor struktur tabel dari berkas `database.sql` yang ada di akar folder (*root*) proyek.

3.  **Menghubungkan Aplikasi ke Database**:
    *   Buka file `config/db.php`.
    *   Sesuaikan konfigurasi koneksi seperti nama *database*, *username*, dan *password*.
        ```php
        $host = '127.0.0.1';
        $db = 'siperuk';     // Sesuaikan dengan nama database Anda
        $user = 'root';      // Username database Anda
        $pass = '';          // Password database Anda
        ```

4.  **Menjalankan Aplikasi**:
    *   Letakkan *folder* proyek ini di dalam direktori server Anda (misal `htdocs` untuk XAMPP).
    *   Akses lewat peramban (*browser*) ke alamat: `http://localhost/antigravity2` (sesuaikan dengan nama *folder* Anda).
    *   Atau jika menggunakan terminal PHP: Jalankan `php -S localhost:8000` di dalam folder proyek, lalu buka `http://localhost:8000`.

---

## 🔑 Akun Uji Coba (Dummy Data)

Gunakan kredensial berikut untuk masuk (*login*) ke dalam sistem. Data ruangan, permohonan, dan jadwal otomatis sudah tersedia apabila Anda menjalankan skrip *seeder* (`seed_dummy.php`).

| Peran | Username | Password |
| :--- | :--- | :--- |
| **Admin Sarpras** | `admin@siperuk.com` | `password123` |
| **Mahasiswa (User)**| `user@siperuk.com` | `password123` |

> *Dibuat menggunakan PHP Native & Vanilla CSS Modern untuk keandalan dan kecepatan tinggi.*
