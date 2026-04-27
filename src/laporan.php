<?php
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

/* ===============================
   DATA BULAN
=================================*/
$bulan = [
    1 => "Januari",
    2 => "Februari",
    3 => "Maret",
    4 => "April",
    5 => "Mei",
    6 => "Juni",
    7 => "Juli",
    8 => "Agustus",
    9 => "September",
    10 => "Oktober",
    11 => "November",
    12 => "Desember"
];

$total = array_fill(1, 12, 0);

/* ===============================
   QUERY (FIX KOLOM)
=================================*/
$query = mysqli_query($conn, "
    SELECT 
        MONTH(tanggal) as bulan,
        SUM(jumlah) as total 
    FROM transaksi_keuangan 
    WHERE jenis='Pemasukan'
    GROUP BY MONTH(tanggal)
");

while ($row = mysqli_fetch_assoc($query)) {
    $bulan_ke = (int)$row['bulan'];
    $total[$bulan_ke] = (int)$row['total'];
}

$nama_bulan = array_values($bulan);
$total_bulan = array_values($total);
?>

<!DOCTYPE html>
<html>
<head>
<title>Laporan - Budi Homestay</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

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
}

.sidebar a:hover {
    background: rgba(255,255,255,0.1);
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
    padding: 20px;
    border-radius: 18px;
    display: flex;
    align-items: center;
}

/* ICON MENU */
.menu-icon {
    cursor: pointer;
    padding: 10px;
}

/* CARD */
.card {
    background: white;
    padding: 25px;
    border-radius: 18px;
    margin-top: 30px;
}

/* OVERLAY */
.overlay {
    position: fixed;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.4);
    top: 0;
    left: 0;
    display: none;
}

.overlay.active {
    display: block;
}

/* HP */
@media (max-width: 768px) {
    .sidebar {
        width: 65%;
        left: -65%;
    }

    .main.shift {
        margin-left: 0;
    }
}
</style>
</head>

<body>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

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

    <div>
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<!-- MAIN -->
<div class="main" id="main">

    <div class="header">
        <div class="menu-icon" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </div>

        <div style="margin-left:20px;">
            <h2></h2>
            <p>Grafik pemasukan per bulan</p>
        </div>
    </div>

    <div class="card">
        <canvas id="myChart"></canvas>
    </div>

</div>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("overlay").classList.toggle("active");
}

function closeSidebar() {
    document.getElementById("sidebar").classList.remove("active");
    document.getElementById("overlay").classList.remove("active");
}

/* CHART */
const ctx = document.getElementById('myChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($nama_bulan); ?>,
        datasets: [{
            label: 'Pemasukan (Rp)',
            data: <?php echo json_encode($total_bulan); ?>,
            backgroundColor: '#2f80ed'
        }]
    }
});
</script>

</body>
</html>