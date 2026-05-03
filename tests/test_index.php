<?php
// test_login.php
include "koneksi.php";

echo "<h2>--- Diagnostic Test: Login System ---</h2>";

// 1. Cek Koneksi
if ($conn) {
    echo "✅ <b>Koneksi Database:</b> Berhasil terhubung.<br>";
} else {
    die("❌ <b>Koneksi Database:</b> Gagal! Cek file koneksi.php.<br>");
}

// 2. Cek Struktur Tabel Admin
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'admin'");
if (mysqli_num_rows($check_table) > 0) {
    echo "✅ <b>Tabel 'admin':</b> Ditemukan.<br>";
} else {
    echo "❌ <b>Tabel 'admin':</b> Tidak ditemukan! Harap buat tabel admin terlebih dahulu.<br>";
}

// 3. Cek Data Admin & Verifikasi Password MD5
// Kita coba cari satu user untuk pengetesan
$query_admin = mysqli_query($conn, "SELECT * FROM admin LIMIT 1");
if (mysqli_num_rows($query_admin) > 0) {
    $data = mysqli_fetch_assoc($query_admin);
    $user_test = $data['username'];
    
    echo "✅ <b>Data Admin:</b> Ditemukan (User: $user_test).<br>";
    
    // Simulasi input password (ganti 'admin123' dengan password asli yang ada di DB untuk testing)
    $password_input = "admin123"; 
    $password_hashed = md5($password_input);
    
    if ($password_hashed === $data['password']) {
        echo "✅ <b>Verifikasi Password:</b> MD5 cocok dengan database.<br>";
    } else {
        echo "⚠️ <b>Verifikasi Password:</b> MD5 tidak cocok. (Pastikan password di DB di-input menggunakan fungsi MD5).<br>";
    }
} else {
    echo "❌ <b>Data Admin:</b> Kosong! Silakan tambah user admin di database.<br>";
}

// 4. Cek Session
session_start();
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "✅ <b>Session:</b> Aktif dan siap digunakan.<br>";
} else {
    echo "❌ <b>Session:</b> Gagal dimulai.<br>";
}

echo "--- Test Selesai ---";
?>