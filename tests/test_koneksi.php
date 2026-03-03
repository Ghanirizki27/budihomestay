<?php
// test_koneksi.php

echo "<h2>--- Database Connection Diagnostic ---</h2>";

// 1. Cek apakah file koneksi ada
if (file_exists("koneksi.php")) {
    echo "✅ <b>File Found:</b> koneksi.php tersedia.<br>";
    include "koneksi.php";
} else {
    die("❌ <b>File Missing:</b> koneksi.php tidak ditemukan di folder ini.");
}

// 2. Cek variabel koneksi
if (isset($conn)) {
    echo "✅ <b>Variable Check:</b> Variabel \$conn sudah terdefinisi.<br>";
} else {
    die("❌ <b>Variable Check:</b> Variabel \$conn tidak ditemukan. Pastikan nama variabel di koneksi.php adalah \$conn.");
}

// 3. Cek Status Koneksi ke MySQL
if ($conn->connect_error) {
    echo "❌ <b>MySQL Status:</b> Koneksi Gagal! Error: " . $conn->connect_error . "<br>";
} else {
    echo "✅ <b>MySQL Status:</b> Terhubung dengan Sukses.<br>";
}

// 4. Cek Informasi Server
echo "<ul>";
echo "<li><b>Host:</b> " . mysqli_get_host_info($conn) . "</li>";
echo "<li><b>Protocol Version:</b> " . mysqli_get_proto_info($conn) . "</li>";
echo "<li><b>Server Version:</b> " . mysqli_get_server_info($conn) . "</li>";
echo "</ul>";

echo "<h3>--- Hasil: Database Siap Tempur! ---</h3>";
?>