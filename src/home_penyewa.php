<?php
session_start();
// Proteksi: Hanya penyewa yang boleh masuk sini
if ($_SESSION['role'] !== 'penyewa') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Halaman Penyewa - Budi Homestay</title>
</head>
<body>
    <h1>Halo, <?php echo $_SESSION['nama']; ?>!</h1>
    <p>Ini adalah halaman khusus untuk Penyewa Kos.</p>
    <p>Fitur Upload Pembayaran dan Keluhan akan muncul di sini.</p>
    <a href="logout.php">Logout</a>
</body>
</html>