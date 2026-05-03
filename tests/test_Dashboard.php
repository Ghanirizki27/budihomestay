<?php
// test_dashboard.php

// 1. Mocking Session (Berpura-pura sudah login)
session_start();
$_SESSION['status'] = "login";
$_SESSION['username'] = "Tester Admin";

echo "<h2>--- Memulai Test Dashboard ---</h2>";

// 2. Test Koneksi Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "budihomestay";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    echo "❌ FAILED: Koneksi database gagal: " . mysqli_connect_error() . "<br>";
} else {
    echo "✅ PASSED: Koneksi database berhasil.<br>";
}

// 3. Test Query Kamar
$kamar = mysqli_query($conn, "SELECT * FROM kamar");
if ($kamar) {
    $total_kamar = mysqli_num_rows($kamar);
    echo "✅ PASSED: Query Kamar berhasil. Total: $total_kamar <br>";
} else {
    echo "❌ FAILED: Query Kamar gagal: " . mysqli_error($conn) . "<br>";
}

// 4. Test Query Kamar Kosong
$kosong = mysqli_query($conn, "SELECT * FROM kamar WHERE status='Kosong'");
if ($kosong) {
    $total_kosong = mysqli_num_rows($kosong);
    echo "✅ PASSED: Query Kamar Kosong berhasil. Total: $total_kosong <br>";
} else {
    echo "❌ FAILED: Query Kamar Kosong gagal.<br>";
}

// 5. Test Query Penghuni
$penghuni = mysqli_query($conn, "SELECT * FROM penghuni");
if ($penghuni) {
    $total_penghuni = mysqli_num_rows($penghuni);
    echo "✅ PASSED: Query Penghuni berhasil. Total: $total_penghuni <br>";
} else {
    echo "❌ FAILED: Query Penghuni gagal.<br>";
}

echo "<h2>--- Test Selesai ---</h2>";

?>

