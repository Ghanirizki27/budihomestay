<?php
session_start();
include_once "auth.php";
requireRole('admin');
include "koneksi.php";
include_once "admin_nav.php";
include_once "db_migrations.php";

ensureAppSchema($conn);

/* ======================
   DATA
====================== */
$kamar = mysqli_query($conn, "SELECT * FROM kamar");
$total_kamar = mysqli_num_rows($kamar);

$kosong = mysqli_query($conn, "SELECT * FROM kamar WHERE status='Kosong'");
$total_kosong = mysqli_num_rows($kosong);

$penyewa = mysqli_query($conn, "SELECT * FROM penyewa");
$total_penghuni = mysqli_num_rows($penyewa);

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

$pengeluaran = mysqli_query($conn, "
    SELECT SUM(jumlah) as total 
    FROM transaksi_keuangan 
    WHERE jenis='Pengeluaran'
    AND MONTH(tanggal)=MONTH(CURDATE())
");
$data_pengeluaran = mysqli_fetch_assoc($pengeluaran);
$total_pengeluaran = $data_pengeluaran['total'] ?? 0;

$keluhan = mysqli_query($conn, "SELECT COUNT(*) AS total FROM laporan_keluhan");
$data_keluhan = mysqli_fetch_assoc($keluhan);
$total_keluhan = $data_keluhan['total'] ?? 0;

$bulan_labels = [];
$grafik_pemasukan = [];
$grafik_transaksi = mysqli_query($conn, "
    SELECT
        MONTH(tanggal) AS bulan,
        SUM(jumlah) AS total
    FROM transaksi_keuangan
    WHERE jenis='Pemasukan' AND YEAR(tanggal)=YEAR(CURDATE())
    GROUP BY MONTH(tanggal)
    ORDER BY MONTH(tanggal)
");

$nama_bulan = [
    1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr",
    5 => "Mei", 6 => "Jun", 7 => "Jul", 8 => "Agu",
    9 => "Sep", 10 => "Okt", 11 => "Nov", 12 => "Des"
];

$total_per_bulan = array_fill(1, 12, 0);
while ($row = mysqli_fetch_assoc($grafik_transaksi)) {
    $total_per_bulan[(int)$row['bulan']] = (int)$row['total'];
}

foreach ($nama_bulan as $nomor => $nama) {
    $bulan_labels[] = $nama;
    $grafik_pemasukan[] = $total_per_bulan[$nomor];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Budi Homestay</title>
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

.sidebar a.active {
    background: rgba(255,255,255,0.14);
    color: #ffffff;
    margin: 6px 12px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 14px;
    backdrop-filter: blur(6px);
}

.menu-bawah a {
    background: #0d2c54;
}

.menu-bawah a.active {
    margin: 0;
    border-radius: 0;
    border: none;
}

/* ===== MAIN ===== */
.main {
    margin-left: 0;
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
    justify-content: space-between;
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

.header-title h2,
.header-title p {
    margin: 0;
}

.header-title p {
    margin-top: 4px;
    opacity: 0.92;
}

/* ===== CARDS ===== */
.cards {
    margin-top: 40px;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
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

.card h3 {
    margin-bottom: 18px;
    font-size: 18px;
}

.chart-card {
    margin-top: 28px;
    padding: 28px;
    border-radius: 18px;
    background: white;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.chart-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.chart-title h3,
.chart-title p {
    margin: 0;
}

.chart-wrapper {
    position: relative;
    height: 330px;
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

    .cards {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .main.shift {
        margin-left: 0; /* biar konten ga geser di HP */
}
}
</style>
</head>

<body>

<?php renderAdminSidebar('dashboard.php'); ?>

<!-- MAIN -->
<div class="main" id="main">

    <!-- HEADER -->
    <div class="header">
        <div style="display:flex; align-items:center; gap:14px;">
            <div class="menu-icon" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </div>
            <div class="header-title">
                <h2>Dashboard</h2>
                <p>Ringkasan okupansi, penghuni, dan pemasukan homestay</p>
            </div>
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
            <i class="fa-solid fa-money-bill"></i>
            <h3>Pemasukan Bulan Ini</h3>
            <p>Rp <?= number_format($total_pemasukan) ?></p>
        </div>

        <div class="card">
            <i class="fa-solid fa-receipt"></i>
            <h3>Pengeluaran Bulan Ini</h3>
            <p>Rp <?= number_format($total_pengeluaran) ?></p>
        </div>

        <div class="card">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <h3>Keluhan</h3>
            <p><?= $total_keluhan ?></p>
        </div>

    </div>

    <div class="chart-card">
        <div class="chart-title">
            <div>
                <h3>Grafik Pemasukan</h3>
                <p>Tren pemasukan per bulan tahun ini</p>
            </div>
            <i class="fa-solid fa-chart-line" style="font-size:28px; color:#2f80ed;"></i>
        </div>

        <div class="chart-wrapper">
            <canvas id="incomeChart"></canvas>
        </div>
    </div>

</div>

<script>
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const main = document.getElementById("main");

function syncSidebarLayout() {
    if (window.innerWidth > 765 && sidebar.classList.contains("active")) {
        main.classList.add("shift");
        overlay.classList.remove("active");
        return;
    }

    main.classList.remove("shift");
}

function toggleSidebar() {
    sidebar.classList.toggle("active");

    if (window.innerWidth <= 765) {
        overlay.classList.toggle("active");
    } else {
        overlay.classList.remove("active");
    }

    syncSidebarLayout();
}

// KHUSUS UNTUK NUTUP
function closeSidebar() {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
    syncSidebarLayout();
}

function updateDateTime() {
    const sekarang = new Date();
    const tanggal = sekarang.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    const waktu = sekarang.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

    document.getElementById("tanggal").textContent = tanggal;
    document.getElementById("waktu").textContent = waktu;
}

const incomeCtx = document.getElementById('incomeChart');
new Chart(incomeCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($bulan_labels); ?>,
        datasets: [{
            label: 'Pemasukan (Rp)',
            data: <?= json_encode($grafik_pemasukan); ?>,
            borderColor: '#2f80ed',
            backgroundColor: 'rgba(47, 128, 237, 0.18)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#ffffff',
            pointBorderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

updateDateTime();
setInterval(updateDateTime, 1000);
window.addEventListener("resize", syncSidebarLayout);
syncSidebarLayout();
</script>

</body>
</html>
