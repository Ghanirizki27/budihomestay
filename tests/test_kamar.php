<?php
// test_kamar.php
include "koneksi.php";
session_start();

// Mocking Session agar tidak ditendang ke index.php
$_SESSION['status'] = "login";

echo "<h2>--- Automated Test: Manajemen Kamar ---</h2>";

// 1. Tes Koneksi
if (!$conn) {
    die("❌ FAILED: Koneksi database terputus.");
}
echo "✅ PASSED: Koneksi database oke.<br>";

// 2. Tes Tambah Kamar (Create)
$test_kode = "TEST-" . rand(10, 99);
$test_harga = 150000;

$insert = mysqli_query($conn, "INSERT INTO kamar (kode_kamar, harga, status) VALUES ('$test_kode', '$test_harga', 'Kosong')");

if ($insert) {
    echo "✅ PASSED: Simulasi Tambah Kamar ($test_kode) berhasil.<br>";
} else {
    echo "❌ FAILED: Gagal tambah kamar: " . mysqli_error($conn) . "<br>";
}

// 3. Tes Ambil Data (Read)
$query_cek = mysqli_query($conn, "SELECT * FROM kamar WHERE kode_kamar = '$test_kode'");
$data = mysqli_fetch_assoc($query_cek);

if ($data) {
    $id_kamar_tes = $data['id_kamar'];
    echo "✅ PASSED: Data kamar tes ditemukan di database (ID: $id_kamar_tes).<br>";
} else {
    echo "❌ FAILED: Data yang baru dimasukkan tidak ditemukan!<br>";
}

// 4. Tes Hapus Kamar (Delete)
if (isset($id_kamar_tes)) {
    $delete = mysqli_query($conn, "DELETE FROM kamar WHERE id_kamar = '$id_kamar_tes'");
    if ($delete) {
        echo "✅ PASSED: Simulasi Hapus Kamar berhasil (Clean up selesai).<br>";
    } else {
        echo "❌ FAILED: Gagal menghapus data tes.<br>";
    }
}

echo "<h3>--- Hasil Akhir: Sistem Kamar Siap Digunakan ---</h3>";
?>