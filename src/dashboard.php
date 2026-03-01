<?php
session_start();
include "config.php";

/* Simulasi login dulu */
$_SESSION['username'] = "Admin";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$data = mysqli_query($conn, "SELECT * FROM kamar");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Manajemen Kos</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="sidebar">
    <h2>KosManajemen</h2>
    <a href="#">Dashboard</a>
    <a href="#">Data Kamar</a>
    <a href="#">Data Penghuni</a>
    <a href="#">Pembayaran</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main">
    <div class="header">
        Selamat Datang, <?php echo $_SESSION['username']; ?>
    </div>

    <h3>Data Kamar Kos</h3>

    <table>
        <tr>
            <th>Kode</th>
            <th>Nama Penghuni</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Alamat</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($data)) { ?>
        <tr>
            <td><?php echo $row['kode_kamar']; ?></td>
            <td><?php echo $row['nama_penghuni']; ?></td>
            <td><?php echo $row['kategori']; ?></td>
            <td>Rp <?php echo number_format($row['harga']); ?></td>
            <td><?php echo $row['alamat']; ?></td>
        </tr>
        <?php } ?>

    </table>
</div>

</body>
</html>