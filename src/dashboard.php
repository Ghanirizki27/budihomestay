<?php
session_start();
include_once "auth.php";
requireRole('admin');
include "koneksi.php";
include_once "admin_nav.php";
include_once "db_migrations.php";

ensureAppSchema($conn);

/* ======================
   DATA COUNTERS
====================== */
$total_kamar = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kamar"));
$total_kosong = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM kamar WHERE status='Kosong'"));
$total_penghuni = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM penyewa"));

// Pemasukan & Pengeluaran Bulan Ini
$total_pemasukan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM pembayaran WHERE status='Lunas' AND MONTH(tanggal_bayar)=MONTH(CURDATE()) AND YEAR(tanggal_bayar)=YEAR(CURDATE())"))['total'] ?? 0;
$total_pengeluaran = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM transaksi_keuangan WHERE jenis='Pengeluaran' AND MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())"))['total'] ?? 0;
$total_keluhan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM laporan_keluhan"))['total'] ?? 0;

/* ======================
   GRAFIK DATA
====================== */
$nama_bulan = [1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "Mei", 6 => "Jun", 7 => "Jul", 8 => "Agu", 9 => "Sep", 10 => "Okt", 11 => "Nov", 12 => "Des"];
$total_per_bulan = array_fill(1, 12, 0);
$grafik_transaksi = mysqli_query($conn, "SELECT MONTH(tanggal_bayar) AS bulan, SUM(jumlah) AS total FROM pembayaran WHERE status='Lunas' AND YEAR(tanggal_bayar)=YEAR(CURDATE()) GROUP BY MONTH(tanggal_bayar)");

while ($row = mysqli_fetch_assoc($grafik_transaksi)) {
    $total_per_bulan[(int)$row['bulan']] = (int)$row['total'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #eaf3ff, #f8fbff);
            color: #13355f;
        }

        /* ===== SIDEBAR & OVERLAY ===== */
        .sidebar {
            width: 250px; height: 100vh;
            background: linear-gradient(180deg, #0f2f59, #123d75);
            position: fixed; left: -250px; top: 0;
            transition: 0.3s; z-index: 1000;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .sidebar.active { left: 0; }
        .sidebar h2 { color: white; text-align: center; padding: 20px; margin: 0; }
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #dbe9ff; text-decoration: none; }
        .sidebar a.active { background: rgba(255,255,255,0.14); color: #ffffff; margin: 6px 12px; border-radius: 14px; }
        
        /* WARNA LOGOUT LEBIH GELAP SESUAI REQUEST */
        .menu-bawah a { background: #081b33; } 
        
        .overlay { position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; top: 0; left: 0; z-index: 999; }
        .overlay.active { display: block; }

        /* ===== MAIN LAYOUT ===== */
        .main { margin-left: 0; padding: 35px; transition: 0.3s; }
        .main.shift { margin-left: 250px; }

        /* ===== HEADER BLUE BOX ===== */
        .header {
            background: linear-gradient(120deg, #1f4f8f, #2f80ed);
            color: white; border-radius: 24px; padding: 28px;
            display: flex; align-items: center; justify-content: space-between;
            gap: 20px; box-shadow: 0 18px 35px rgba(47,128,237,0.25);
        }
        .header h1, .header p { margin: 0; }
        .header p { margin-top: 8px; opacity: 0.9; font-size: 14px; }
        .menu-icon {
            cursor: pointer; padding: 10px; border-radius: 10px; font-size: 20px;
            background: rgba(255,255,255,0.15); display: flex; align-items: center;
        }

        /* ===== GRID & CARDS ===== */
        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px; margin-top: 24px;
        }
        .card {
            background: white; border-radius: 20px; padding: 24px;
            box-shadow: 0 12px 28px rgba(18,61,117,0.08); text-align: center;
        }
        .card i { font-size: 35px; color: #2f80ed; margin-bottom: 15px; display: block; }
        .card h3 { margin: 0; font-size: 16px; color: #5c6f87; }
        .metric { font-size: 30px; font-weight: 800; color: #0f3c74; margin-top: 10px; }

        .chart-card {
            background: white; border-radius: 20px; padding: 28px;
            box-shadow: 0 12px 28px rgba(18,61,117,0.08); margin-top: 24px;
        }
        .chart-wrapper { position: relative; height: 350px; margin-top: 20px; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
            .sidebar { width: 70%; left: -70%; }
            .main.shift { margin-left: 0; }
            .header { flex-direction: column; align-items: flex-start; }
            .header-info-right { text-align: left !important; margin-top: 15px; }
        }
    </style>
</head>
<body>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderAdminSidebar('dashboard.php'); ?>

    <div class="main" id="main">
        <!-- HEADER BLUE BOX -->
        <div class="header">
            <!-- ALIGN ITEMS CENTER UNTUK MEMASTIKAN ICON & TEKS SEJAJAR TENGAH -->
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </div>
                <div>
                    <h1 style="font-size: 24px;">Selamat Datang, Admin!</h1>
                    <p>Ringkasan okupansi dan tren pemasukan Budi Homestay hari ini.</p>
                </div>
            </div>
            
            <!-- BAGIAN KANAN YANG SUDAH DISET AGAR LEBIH TENGAH SECARA VERTIKAL -->
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">Level: Administrator</div>
            </div>
        </div>

        <!-- STATS GRID -->
        <div class="grid">
            <div class="card">
                <i class="fa-solid fa-door-open"></i>
                <h3>Total Kamar</h3>
                <div class="metric"><?= $total_kamar ?></div>
            </div>
            <div class="card">
                <i class="fa-solid fa-check-circle"></i>
                <h3>Kamar Kosong</h3>
                <div class="metric"><?= $total_kosong ?></div>
            </div>
            <div class="card">
                <i class="fa-solid fa-user-group"></i>
                <h3>Total Penghuni</h3>
                <div class="metric"><?= $total_penghuni ?></div>
            </div>
            <div class="card">
                <i class="fa-solid fa-money-bill-wave"></i>
                <h3>Pemasukan Bulan Ini</h3>
                <div class="metric">Rp <?= number_format($total_pemasukan) ?></div>
            </div>
            <div class="card">
                <i class="fa-solid fa-receipt"></i>
                <h3>Pengeluaran Bulan Ini</h3>
                <div class="metric">Rp <?= number_format($total_pengeluaran) ?></div>
            </div>
            <div class="card">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <h3>Laporan Keluhan</h3>
                <div class="metric"><?= $total_keluhan ?></div>
            </div>
        </div>

        <!-- CHART AREA -->
        <div class="chart-card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3 style="margin:0;">Grafik Tren Pemasukan</h3>
                    <p style="margin:5px 0 0; color:#6e7f95; font-size:14px;">Data riwayat pembayaran lunas tahun <?= date('Y') ?></p>
                </div>
                <i class="fa-solid fa-chart-line" style="font-size:24px; color:#2f80ed;"></i>
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
        if (window.innerWidth > 900 && sidebar.classList.contains("active")) {
            main.classList.add("shift");
            return;
        }
        main.classList.remove("shift");
    }

    function toggleSidebar() {
        sidebar.classList.toggle("active");
        if (window.innerWidth <= 900) {
            overlay.classList.toggle("active");
        }
        syncSidebarLayout();
    }

    function closeSidebar() {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
        syncSidebarLayout();
    }

    // Real-time Clock
    function updateDateTime() {
        const skrg = new Date();
        document.getElementById("tanggal").textContent = skrg.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        document.getElementById("waktu").textContent = skrg.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setInterval(updateDateTime, 1000);
    updateDateTime();

    // Chart.js
    const ctx = document.getElementById('incomeChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_values($nama_bulan)); ?>,
            datasets: [{
                label: 'Pemasukan (Rp)',
                data: <?= json_encode(array_values($total_per_bulan)); ?>,
                borderColor: '#2f80ed',
                backgroundColor: 'rgba(47, 128, 237, 0.15)',
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#fff',
                pointBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    window.addEventListener("resize", syncSidebarLayout);
    syncSidebarLayout();
    </script>
</body>
</html>