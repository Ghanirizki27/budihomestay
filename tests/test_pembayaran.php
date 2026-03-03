<?php
// test_pembayaran.php
include "koneksi.php";
session_start();

// Mocking Session
$_SESSION['status'] = "login";

echo "<h2>--- Diagnostic Test: Sistem Pembayaran ---</h2>";

// 1. Cek Relasi Penghuni
$cek_penghuni = mysqli_query($conn, "SELECT id_penghuni FROM penghuni LIMIT 1");
$penghuni = mysqli_fetch_assoc($cek_penghuni);

if (!$penghuni) {
    die("❌ <b>FAILED:</b> Tidak ada data penghuni. Tambahkan penghuni dulu sebelum mengetes pembayaran.");
} else {
    $id_p = $penghuni['id_penghuni'];
    echo "✅ <b>Data Penghuni:</b> Tersedia (ID: $id_p).<br>";
}

// 2. Simulasi Tambah Pembayaran
$tgl = date('Y-m-d');
$simulasi_tambah = mysqli_query($conn, "
    INSERT INTO pembayaran (id_penghuni, bulan, tahun, tanggal_bayar, jumlah_bayar, status)
    VALUES ('$id_p', 'Januari', '2026', '$tgl', '500000', 'Lunas')
");

if ($simulasi_tambah) {
    $id_baru = mysqli_insert_id($conn);
    echo "✅ <b>Insert Pembayaran:</b> Berhasil (ID: $id_baru).<br>";
} else {
    echo "❌ <b>Insert Pembayaran:</b> Gagal! " . mysqli_error($conn) . "<br>";
}

// 3. Tes Query JOIN (Sesuai kode asli kamu)
$query_join = mysqli_query($conn, "
    SELECT pembayaran.*, penghuni.nama_penghuni, kamar.kode_kamar
    FROM pembayaran
    JOIN penghuni ON pembayaran.id_penghuni = penghuni.id_penghuni
    JOIN kamar ON penghuni.id_kamar = kamar.id_kamar
    WHERE pembayaran.id_pembayaran = '$id_baru'
");

if (mysqli_num_rows($query_join) > 0) {
    $data = mysqli_fetch_assoc($query_join);
    echo "✅ <b>Relasi Database:</b> Berhasil (Nama: " . $data['nama_penghuni'] . ", Kamar: " . $data['kode_kamar'] . ").<br>";
} else {
    echo "❌ <b>Relasi Database:</b> Gagal! Pastikan id_kamar di tabel penghuni cocok dengan id_kamar di tabel kamar.<br>";
}

// 4. Clean Up (Hapus data tes)
if (isset($id_baru)) {
    mysqli_query($conn, "DELETE FROM pembayaran WHERE id_pembayaran = '$id_baru'");
    echo "✅ <b>Clean up:</b> Data testing telah dihapus.<br>";
}

echo "<h3>--- Diagnosa Selesai: Sistem Pembayaran Normal ---</h3>";
?>