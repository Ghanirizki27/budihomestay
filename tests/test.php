<?php
session_start();
include "koneksi.php";

echo "<h2>Pengecekan Sistem Budi Homestay</h2>";
echo "<hr>";

// 1. Cek Koneksi Database
echo "<h3>1. Status Koneksi Database</h3>";
if ($conn) {
    echo "<span style='color:green; font-weight:bold;'>BERHASIL:</span> Terhubung ke database.";
} else {
    echo "<span style='color:red; font-weight:bold;'>GAGAL:</span> " . mysqli_connect_error();
}

echo "<hr>";

// 2. Cek Data di Tabel Admin
echo "<h3>2. Data di Tabel Admin</h3>";
$query = mysqli_query($conn, "SELECT id_admin, username, nama_lengkap FROM admin");
if ($query) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Nama</th>
            </tr>";
    while ($row = mysqli_fetch_assoc($query)) {
        echo "<tr>
                <td>{$row['id_admin']}</td>
                <td>{$row['username']}</td>
                <td>{$row['nama_lengkap']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "Gagal mengambil data: " . mysqli_error($conn);
}

echo "<hr>";

// 3. Cek Status Session (Penyebab Redirect Loop)
echo "<h3>3. Status Session Saat Ini</h3>";
if (isset($_SESSION['status'])) {
    echo "Status Login: <b>" . $_SESSION['status'] . "</b><br>";
    echo "Username: " . ($_SESSION['username'] ?? 'Tidak ada') . "<br>";
    echo "ID Admin: " . ($_SESSION['id_admin'] ?? 'Tidak ada') . "<br>";
    echo "<br><a href='logout.php' style='color:red;'>Hapus Session (Logout)</a>";
} else {
    echo "<span style='color:orange;'>Belum ada session login yang aktif.</span>";
}

echo "<hr>";

// 4. Cek Versi PHP
echo "<h3>4. Informasi Server</h3>";
echo "Versi PHP: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'];
?>