1. index.php
Fungsi: Halaman utama/aplikasi. Biasanya menjadi pintu masuk pertama. Mungkin langsung mengarahkan ke halaman login atau dashboard tergantung status login.

File Autentikasi

2. login.php
Fungsi: Halaman untuk proses masuk ke sistem. Menerima input username dan password, kemudian memverifikasi ke database.

3. logout.php
Fungsi:Proses keluar dari sistem. Menghapus session user dan mengarahkan kembali ke halaman login.

File Koneksi

4. koneksi.php
Fungsi: File konfigurasi koneksi database. Berisi script untuk menghubungkan PHP dengan MySQL (host, username, password, nama database). File ini akan di-include oleh file-file lain yang membutuhkan akses database.

File Halaman Utama (Menu)

5. dashboard.php
Fungsi: Halaman beranda setelah login. Menampilkan ringkasan data:
- Total kamar
- Kamar kosong
- Total penghuni
- Waktu saat ini

6. kamar.php
Fungsi: Halaman untuk mengelola data kamar. Berisi:
- Form tambah kamar
- Tabel daftar kamar
- Tombol hapus kamar

7. penghuni.php
Fungsi: Halaman untuk mengelola data penghuni. Berisi:
- Form tambah penghuni
- Dropdown pilih kamar kosong
- Tabel daftar penghuni
- Tombol hapus penghuni

8. pembayaran.php
Fungsi: Halaman untuk mengelola pembayaran sewa. Berisi:
- Form tambah pembayaran
- Tabel riwayat pembayaran
- Status Lunas/Belum

9. laporan.php
Fungsi: Halaman laporan keuangan. Menampilkan:
- Grafik pemasukan per bulan
- Total pemasukan

10. peraturan.php
Fungsi: Halaman yang berisi tata tertib dan peraturan homestay. Menampilkan:
- Kewajiban penghuni
- Peraturan bertamu
- Larangan dan sanksi

> File Pendukung

11. bg-kos.jpg
Fungsi: File gambar background untuk halaman login atau halaman lainnya. Biasanya digunakan sebagai latar belakang agar tampilan lebih menarik.

12. stle.css
Fungsi: File CSS untuk styling tampilan. Ada kesalahan penulisan, seharusnya:
- style.css (tanpa tanda # dan typo "stle")

Kegunaan: Mengatur tampilan visual seperti:
- Warna background
- Ukuran font
- Layout tabel dan form
- Responsive design

