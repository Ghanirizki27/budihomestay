<?php
// test_penghuni.php
include "koneksi.php";
session_start();
$_SESSION['status'] = "login";

echo "<h2>--- Diagnostic Test: Siklus Penghuni ---</h2>";

// 1. Cari Kamar yang Sedang Kosong
$cek_kamar = mysqli_query($conn, "SELECT id_kamar, kode_kamar FROM kamar WHERE status='Kosong' LIMIT 1");
$kamar = mysqli_fetch_assoc($cek_kamar);

if (!$kamar) {
    die("❌ <b>FAILED:</b> Tidak ada kamar kosong untuk testing. Buat kamar baru dulu.");
}
$id_k = $kamar['id_kamar'];
$kode_k = $kamar['kode_kamar'];
echo "✅ <b>Kamar Tes:</b> Menggunakan kamar $kode_k (ID: $id_k).<br>";

// 2. Simulasi Tambah Penghuni
$tgl = date('Y-m-d');
$insert = mysqli_query($conn, "
    INSERT INTO penghuni (nama_penghuni, no_ktp, no_hp, alamat, id_kamar, tanggal_masuk)
    VALUES ('Tes Robot', '123456', '0812', 'Bumi', '$id_k', '$tgl')
");

if ($insert) {
    $id_p = mysqli_insert_id($conn);
    // Jalankan update status kamar (seperti di kode penghuni.php kamu)
    mysqli_query($conn, "UPDATE kamar SET status='Terisi' WHERE id_kamar='$id_k'");
    echo "✅ <b>Tambah Penghuni:</b> Berhasil.<br>";
}

// 3. Verifikasi Status Kamar (Harus Terisi)
$cek_status = mysqli_query($conn, "SELECT status FROM kamar WHERE id_kamar='$id_k'");
$s = mysqli_fetch_assoc($cek_status);
if ($s['status'] == 'Terisi') {
    echo "✅ <b>Otomatisasi Status:</b> Berhasil (Kamar berubah jadi Terisi).<br>";
} else {
    echo "❌ <b>Otomatisasi Status:</b> GAGAL! Kamar tetap Kosong.<br>";
}

// 4. Simulasi Hapus & Kembalikan Status Kamar
$delete = mysqli_query($conn, "DELETE FROM penghuni WHERE id_penghuni='$id_p'");
mysqli_query($conn, "UPDATE kamar SET status='Kosong' WHERE id_kamar='$id_k'");

$cek_akhir = mysqli_query($conn, "SELECT status FROM kamar WHERE id_kamar='$id_k'");
$sa = mysqli_fetch_assoc($cek_akhir);
if ($sa['status'] == 'Kosong') {
    echo "✅ <b>Clean up:</b> Berhasil (Kamar kembali Kosong).<br>";
}

echo "<h3>--- Diagnosa Selesai ---</h3>";
?>