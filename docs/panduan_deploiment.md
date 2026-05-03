Panduan Deployment Sistem

Ikuti langkah-langkah berikut untuk menjalankan aplikasi di lingkungan lokal:

1. **Persiapan Database**:
   * Buka phpMyAdmin.
   * Buat database baru dengan nama `budi_homestay`.
   * Import file `budihomestay.sql` yang tersedia di root folder.

2. **Konfigurasi Koneksi**:
   * Pastikan file koneksi di folder `src/` sudah sesuai dengan kredensial database lokal Anda (Host, User, Password).

3. **Menjalankan Server**:
   * Letakkan folder proyek di dalam direktori `htdocs` (untuk pengguna XAMPP).
   * Akses melalui browser di alamat: `http://localhost/budihomestay/`.

4. **Kredensial Default**:
   * **Username**: andi
   * **Password**: [Isi sesuai data di database Anda]