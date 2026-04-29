# 📱 APLIKASI MANAJEMEN BUDI HOMESTAY
**Dokumentasi Fitur - 7 Modul Utama**

---

## 🚪 FITUR 1: LOGIN

**Deskripsi**
Halaman awal untuk mengakses sistem. Pengguna harus memasukkan username dan password yang benar untuk masuk ke aplikasi.

**Input**
| Field | Tipe Data | Keterangan |
|-------|-----------|------------|
| Username | Teks | Nama pengguna terdaftar |
| Password | Teks tersembunyi | Kata sandi (***) |

**Output**
- ✅ **Berhasil:** Redirect ke halaman Dashboard
- ❌ **Gagal:** Muncul pesan error "Username atau password salah"

---

## 📊 FITUR 2: DASHBOARD

**Deskripsi**
Halaman utama yang menampilkan ringkasan data homestay secara real-time.

**Input**
- *Tidak ada input dari user* (data otomatis diambil dari database)

**Output**
| Komponen | Keterangan |
|----------|------------|
| **Total Kamar** | Angka jumlah seluruh kamar |
| **Kamar Kosong** | Angka kamar yang tersedia |
| **Total Penghuni** | Angka penghuni aktif |
| **Waktu** | Tanggal dan waktu saat ini |

---

## 🏠 FITUR 3: DATA KAMAR

**Deskripsi**
Halaman untuk mengelola data kamar (tambah, lihat, hapus).

**Input (Form Tambah Kamar)**
| Field | Tipe Data | Contoh |
|-------|-----------|--------|
| Kode Kamar | Teks | A01, K1, K2 |
| Harga Sewa | Angka | 500000 |
| Tombol Simpan | Button | - |

**Output**
- ✅ Notifikasi: "Data kamar berhasil ditambahkan"
- 📋 **Tabel Daftar Kamar:**
  | No | Kode Kamar | Harga | Status | Aksi |
  |----|------------|-------|--------|------|
  | 1 | K1 | Rp 500.000 | Terisi | Hapus |
  | 2 | K2 | Rp 600.000 | Kosong | Hapus |

---

## 👥 FITUR 4: DATA PENGHUNI

**Deskripsi**
Halaman untuk mengelola data penghuni (tambah, lihat, hapus) yang otomatis mengupdate status kamar.

**Input (Form Tambah Penghuni)**
| Field | Tipe Data | Keterangan |
|-------|-----------|------------|
| Nama Penghuni | Teks | Nama lengkap |
| No KTP | Teks | Nomor induk kependudukan |
| No HP | Teks | Nomor telepon |
| Alamat | Teks | Alamat asal |
| Pilih Kamar Kosong | Dropdown | List kamar status "Kosong" |
| Tanggal Masuk | Date | dd/mm/yyyy |
| Tombol Simpan | Button | - |

**Output**
- ✅ Notifikasi: "Data penghuni berhasil ditambahkan"
- 📋 **Tabel Daftar Penghuni:**
  | No | Nama | No KTP | No HP | Alamat | Kamar | Tgl Masuk | Aksi |
  |----|------|--------|-------|--------|-------|-----------|------|
  | 1 | Andri | 3247... | 0399... | jl mugarsari | K1 | 11-09-2005 | Hapus |
- 🔄 **Status kamar berubah** dari "Kosong" menjadi "Terisi"

---

## 💰 FITUR 5: PEMBAYARAN

**Deskripsi**
Halaman untuk mencatat pembayaran sewa dan melihat riwayat transaksi penghuni.

**Input (Form Tambah Pembayaran)**
| Field | Tipe Data | Keterangan |
|-------|-----------|------------|
| Pilih Penghuni | Dropdown | List semua penghuni |
| Bulan | Dropdown | Januari s/d Desember |
| Tahun | Angka | Contoh: 2026 |
| Tanggal Bayar | Date | dd/mm/yyyy |
| Jumlah Bayar | Angka | Nominal pembayaran |
| Status | Checkbox | Lunas / Belum Lunas |
| Tombol Simpan | Button | - |

**Output**
- ✅ Notifikasi: "Pembayaran berhasil dicatat"
- 📋 **Tabel Riwayat Pembayaran:**
  | No | Nama | Kamar | Bulan | Tahun | Tgl Bayar | Jumlah | Status | Aksi |
  |----|------|-------|-------|-------|-----------|--------|--------|------|
  | 1 | GAHNI | K2 | Maret | 2026 | 22-03-26 | Rp 500.000 | Lunas | Hapus |

---

## 📈 FITUR 6: LAPORAN

**Deskripsi**
Halaman untuk melihat laporan keuangan dalam bentuk grafik visual.

**Input**
- *Tidak ada input langsung* (data diambil dari tabel pembayaran)

**Output**
| Komponen | Keterangan |
|----------|------------|
| **Grafik Batang/Line** | Visualisasi pemasukan per bulan (Januari-Desember) |
| **Sumbu X** | Bulan (Jan, Feb, Mar, ... Des) |
| **Sumbu Y** | Nominal pemasukan (Rupiah) |
| **Total Pemasukan** | Akumulasi seluruh pemasukan (angka) |

```
📊 Contoh Visual:
Rp 6jt ┤        █
Rp 5jt ┤     █  █  █
Rp 4jt ┤  █  █  █  █  █  █
Rp 3jt ┤  █  █  █  █  █  █  █  █  █
       └──────────────────────────
          J  F  M  A  M  J  J  A  S  O  N  D
```

---

## 📜 FITUR 7: PERATURAN KOST

**Deskripsi**
Halaman statis yang berisi tata tertib dan peraturan untuk penghuni.

**Input**
- *Tidak ada input* (hanya menampilkan konten)

**Output**

### 📋 Daftar Kewajiban Penghuni
- Menyerahkan fotokopi KTP
- Membayar sewa maksimal tanggal 5
- Menjaga kebersihan kamar dan area bersama
- Mematikan lampu dan elektronik saat keluar
- Mengunci pintu pagar
- Waktu malam pukul 22.00 WIB
- Menjaga ketenangan lingkungan

### 🚪 Peraturan Bertamu
- Jam bertamu maksimal pukul 22.00 WIB
- Tamu lawan jenis dilarang masuk kamar
- Tamu menginap wajib lapor
- Penghuni bertanggung jawab atas tamu
- Dilarang membuat kegaduhan

### ⚠️ Larangan Keras (Sanksi DO)
1. Narkoba dan Miras
2. Tindakan asusila/perjudian
3. Membawa senjata tajam
4. Membuat kegaduhan
5. Merusak fasilitas

### 💸 Tabel Denda & Sanksi
| Jenis Pelanggaran | Sanksi / Denda |
|-------------------|----------------|
| Terlambat bayar | Rp 10.000/hari |
| Tamu lawan jenis ke kamar | Teguran & Peringatan |
| Gaduh setelah jam 22.00 | Peringatan Tertulis |

---

## 📁 STRUKTUR FILE

```
src/
├── index.php              # Halaman utama
├── login.php              # Proses autentikasi
├── logout.php             # Keluar sistem
├── koneksi.php            # Koneksi database
├── dashboard.php          # Ringkasan data
├── kamar.php              # Manajemen kamar
├── penghuni.php           # Manajemen penghuni
├── pembayaran.php         # Manajemen pembayaran
├── laporan.php            # Laporan grafik
├── Peraturan kost.php     # Tata tertib
├── bg-kos.jpg             # Background image
└── style.css              # Styling CSS
```

---

**© 2026 Budi Homestay - Sistem Informasi Manajemen Kost**