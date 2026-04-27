<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

include "koneksi.php";

/* ======================
   DATA
====================== */
$kamar = mysqli_query($conn, "SELECT * FROM kamar");
$total_kamar = mysqli_num_rows($kamar);

$kosong = mysqli_query($conn, "SELECT * FROM kamar WHERE status='Kosong'");
$total_kosong = mysqli_num_rows($kosong);

$penyewa = mysqli_query($conn, "SELECT * FROM penyewa");
$total_penghuni = mysqli_num_rows($penyewa);

// Penyewa aktif (sementara = total penghuni)
$total_aktif = $total_penghuni;

/* ======================
   PEMASUKAN BULAN INI
====================== */
$pemasukan = mysqli_query($conn, "
    SELECT SUM(jumlah) as total 
    FROM transaksi_keuangan 
    WHERE jenis='Pemasukan'
    AND MONTH(tanggal)=MONTH(CURDATE())
");

$data_pemasukan = mysqli_fetch_assoc($pemasukan);
$total_pemasukan = $data_pemasukan['total'] ?? 0;

// Keluhan (sementara 0)
$total_keluhan = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
<style>
    
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #eaf3ff, #f8fbff);
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: 250px;
    height: 100vh;
    background: linear-gradient(180deg, #0f2f59, #123d75);
    position: fixed;
    left: -250px;
    top: 0;
    transition: 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 5px 0 25px rgba(0,0,0,0.08);
}

.sidebar.active {
    left: 0;
}

.sidebar h2 {
    color: white;
    text-align: center;
    padding: 20px;
    margin: 0;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    color: #dbe9ff;
    text-decoration: none;
    transition: 0.3s;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.1);
    padding-left: 28px;
}

.menu-bawah a {
    background: #0d2c54;
}

/* ===== MAIN ===== */
.main {
    padding: 35px;
    transition: 0.3s;
}

.main.shift {
    margin-left: 250px;
}

/* ===== HEADER ===== */
.header {
    background: linear-gradient(90deg, #4da6ff, #2f80ed);
    color: white;
    padding: 20px 25px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    box-shadow: 0 15px 30px rgba(47,128,237,0.25);
}

/* ICON GARIS 3 */
.menu-icon {
    font-size: 20px;
    cursor: pointer;
    color: white;
    padding: 10px;
    border-radius: 8px;
    transition: 0.3s;
}

.menu-icon:hover {
    background: rgba(255,255,255,0.2);
}

/* ===== CARDS ===== */
.cards {
    margin-top: 40px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.card {
    padding: 30px;
    border-radius: 18px;
    background: white;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    text-align: center;
}

.card i {
    font-size: 40px;
    margin-bottom: 12px;
    color: #2f80ed;
}

.card p {
    font-size: 28px;
    font-weight: bold;
    color: #0f2f59;
}
/* ================= RESPONSIVE HP ================= */
@media (max-width: 765px) {

    .sidebar {
        width: 65%; /* hampir full layar */
        left: -65%;
    }

    .sidebar.active {
        left: 0;
    }

    .main {
        padding: 20px;
    }

    .main.shift {
        margin-left: 0; /* biar konten ga geser di HP */
}

.overlay {
    position: fixed;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.4);
    top: 0;
    left: 0;
    display: none;
    z-index: 999;
}

.overlay.active {
    display: block;
}

}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div>
        <h2><i class="fa-solid fa-house"></i> Budi Homestay</h2>

        <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="kamar.php"><i class="fa-solid fa-bed"></i> Data Kamar</a>
        <a href="penghuni.php"><i class="fa-solid fa-users"></i> Data Penyewa</a>
        <a href="pembayaran.php"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a>
        <a href="laporan.php"><i class="fa-solid fa-chart-column"></i> Laporan Keuangan</a>
        <a href="peraturan.php"><i class="fa-solid fa-book"></i> Pengumuman</a>
    </div>

    <div class="menu-bawah">
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>

<!-- MAIN -->
<div class="main" id="main">

    <!-- HEADER -->
    <div class="header">

        <div class="menu-icon" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </div>

        <div style="margin-left:auto; text-align:right;">
            <div id="tanggal"></div>
            <div id="waktu" style="font-size:20px; font-weight:bold;"></div>
        </div>

    </div>

    <!-- CARDS -->
    <div class="cards">

        <div class="card">
            <i class="fa-solid fa-door-open"></i>
            <h3>Total Kamar</h3>
            <p><?= $total_kamar ?></p>
        </div>

        <div class="card">
            <i class="fa-solid fa-check-circle"></i>
            <h3>Kamar Kosong</h3>
            <p><?= $total_kosong ?></p>
        </div>

        <div class="card">
            <i class="fa-solid fa-user"></i>
            <h3>Total Penghuni</h3>
            <p><?= $total_penghuni ?></p>
        </div>

        <div class="card">
            <i class="fa-solid fa-users"></i>
            <h3>Penyewa Aktif</h3>
            <p><?= $total_aktif ?></p>
        </div>

        <div class="card">
            <i class="fa-solid fa-money-bill"></i>
            <h3>Pemasukan Bulan Ini</h3>
            <p>Rp <?= number_format($total_pemasukan) ?></p>
        </div>

        <div class="card">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <h3>Keluhan</h3>
            <p><?= $total_keluhan ?></p>
        </div>

    </div>

</div>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("overlay").classList.toggle("active");
}

// KHUSUS UNTUK NUTUP
function closeSidebar() {
    document.getElementById("sidebar").classList.remove("active");
    document.getElementById("overlay").classList.remove("active");
}

updateDateTime();
setInterval(updateDateTime, 1000);
</script>

</body>
</html>