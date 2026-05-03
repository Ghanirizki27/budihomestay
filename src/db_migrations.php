<?php

function ensureAppSchema(mysqli $conn): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $queries = [
        "CREATE TABLE IF NOT EXISTS pengumuman (
            id_pengumuman INT AUTO_INCREMENT PRIMARY KEY,
            id_admin INT NULL,
            judul VARCHAR(100) NOT NULL,
            isi TEXT NOT NULL,
            tanggal_dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS peraturan (
            id_peraturan INT AUTO_INCREMENT PRIMARY KEY,
            isi_peraturan TEXT NOT NULL,
            kategori VARCHAR(50) DEFAULT 'Umum',
            tanggal_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS laporan_keluhan (
            id_keluhan INT AUTO_INCREMENT PRIMARY KEY,
            id_penyewa INT NULL,
            isi_keluhan TEXT NULL,
            tanggal DATE NULL,
            status ENUM('Diajukan','Diproses','Selesai') DEFAULT 'Diajukan'
        )",
        "CREATE TABLE IF NOT EXISTS transaksi_keuangan (
            id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
            id_penyewa INT NULL,
            tanggal DATE NULL,
            jenis ENUM('Pemasukan','Pengeluaran') NOT NULL,
            jumlah INT NOT NULL,
            keterangan TEXT NULL
        )",
        "CREATE TABLE IF NOT EXISTS penarikan_saldo (
            id_penarikan INT AUTO_INCREMENT PRIMARY KEY,
            jumlah INT NOT NULL,
            metode ENUM('Dana','Transfer Bank','E-Wallet Lainnya') NOT NULL,
            status ENUM('Pending','Disetujui','Ditolak','Selesai') DEFAULT 'Pending',
            tanggal_request DATE NOT NULL,
            tanggal_selesai DATE NULL,
            keterangan TEXT NULL,
            id_admin INT NULL
        )",
        "ALTER TABLE penyewa ADD COLUMN IF NOT EXISTS nomor_ktp VARCHAR(30) NULL AFTER nama",
        "ALTER TABLE penyewa ADD COLUMN IF NOT EXISTS foto_ktp VARCHAR(255) NULL AFTER nomor_ktp",
        "ALTER TABLE penyewa ADD COLUMN IF NOT EXISTS jenis_kelamin ENUM('Laki-laki','Perempuan') NULL AFTER nomor_ktp",
        "ALTER TABLE penyewa ADD COLUMN IF NOT EXISTS alamat TEXT NULL AFTER jenis_kelamin",
        "ALTER TABLE penyewa ADD COLUMN IF NOT EXISTS pekerjaan VARCHAR(100) NULL AFTER alamat",
        "ALTER TABLE penyewa ADD COLUMN IF NOT EXISTS metode_pembayaran ENUM('Dana','Transfer Bank','Cash') NULL AFTER pekerjaan",
        "ALTER TABLE penyewa ADD COLUMN IF NOT EXISTS status_sewa ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif' AFTER metode_pembayaran",
        "ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS metode_pembayaran ENUM('Dana','Transfer Bank','Cash') NULL AFTER jumlah",
        "ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS jatuh_tempo DATE NULL AFTER tanggal_bayar",
        "ALTER TABLE admin ADD COLUMN IF NOT EXISTS id_penyewa INT NULL AFTER role",
        "ALTER TABLE laporan_keluhan ADD COLUMN IF NOT EXISTS tanggapan_admin TEXT NULL AFTER status",
    ];

    foreach ($queries as $query) {
        mysqli_query($conn, $query);
    }

    $defaultPeraturan = [
        ['Kewajiban', 'Menyerahkan fotokopi KTP atau identitas diri yang sah saat mendaftar.'],
        ['Kewajiban', 'Membayar uang sewa kost paling lambat tanggal 5 setiap bulannya.'],
        ['Kewajiban', 'Menjaga kebersihan kamar masing-masing dan area bersama.'],
        ['Kewajiban', 'Mematikan lampu dan alat elektronik saat meninggalkan kamar.'],
        ['Peraturan Bertamu', 'Jam bertamu maksimal hingga pukul 22.00 WIB.'],
        ['Peraturan Bertamu', 'Tamu lawan jenis dilarang masuk ke dalam kamar dan hanya boleh di area umum.'],
        ['Larangan Keras', 'Membawa, menggunakan, atau mengedarkan narkoba dan minuman keras.'],
        ['Larangan Keras', 'Merusak fasilitas kost secara sengaja.'],
        ['Denda & Sanksi', 'Keterlambatan pembayaran lebih dari tanggal 5: peringatan.'],
    ];

    $cekPeraturan = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peraturan");
    $totalPeraturan = mysqli_fetch_assoc($cekPeraturan);
    if ((int) ($totalPeraturan['total'] ?? 0) === 0) {
        foreach ($defaultPeraturan as [$kategori, $isi]) {
            $kategoriEscaped = mysqli_real_escape_string($conn, $kategori);
            $isiEscaped = mysqli_real_escape_string($conn, $isi);
            mysqli_query($conn, "
                INSERT INTO peraturan (kategori, isi_peraturan)
                VALUES ('$kategoriEscaped', '$isiEscaped')
            ");
        }
    }

    mysqli_query($conn, "
        UPDATE admin
        JOIN penyewa ON admin.nama_lengkap = penyewa.nama
        SET admin.id_penyewa = penyewa.id_penyewa
        WHERE admin.role = 'penyewa' AND admin.id_penyewa IS NULL
    ");

    $checked = true;
}
