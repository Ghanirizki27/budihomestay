<?php
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

/* ===============================
   SIAPKAN 12 BULAN
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

// Default semua 0
$total = array_fill(1, 12, 0);

/* ===============================
   AMBIL DATA DARI DATABASE
=================================*/
$query = mysqli_query($conn, "
    SELECT 
        MONTH(tanggal_bayar) as bulan,
        SUM(jumlah_bayar) as total 
    FROM pembayaran 
    WHERE status='Lunas'
    GROUP BY MONTH(tanggal_bayar)
");

while ($row = mysqli_fetch_assoc($query)) {
    $bulan_ke = (int)$row['bulan'];
    $total[$bulan_ke] = (int)$row['total'];
}

// Format untuk ChartJS
$nama_bulan = array_values($bulan);
$total_bulan = array_values($total);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keuangan</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Chart JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f4f9ff;
        }

        /* Sidebar */
        .sidebar {
    width: 250px;
    height: 100vh;
    background: linear-gradient(180deg, #0f2f59, #123d75);
    position: fixed;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 5px 0 25px rgba(0,0,0,0.08);
}

        .sidebar h2 {
            color: white;
            text-align: center;
            padding: 20px 10px;
            margin: 0;
            font-size: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: #dbe9ff;
            text-decoration: none;
            transition: 0.3s ease;
            font-size: 15px;
        }

        .sidebar a i {
            width: 20px;
        }

        .sidebar a:hover {
            background: rgba(255,255,255,0.08);
            padding-left: 28px;
        }

        .menu-bawah a {
            background: #0d2c54;
        }

        .menu-bawah a:hover {
            background: #123d75;
        }

        .main {
            margin-left: 240px;
            padding: 30px;
        }

        .header {
            background: linear-gradient(90deg, #4da6ff, #80c1ff);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="menu-atas">
        <h2><i class="fa-solid fa-house"></i> Budi Homestay</h2>

        <a href="dashboard.php">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>

        <a href="kamar.php">
            <i class="fa-solid fa-bed"></i> Data Kamar
        </a>

        <a href="penghuni.php">
            <i class="fa-solid fa-users"></i> Data Penghuni
        </a>

        <a href="pembayaran.php">
            <i class="fa-solid fa-money-bill-wave"></i> Pembayaran
        </a>

        <a href="laporan.php">
            <i class="fa-solid fa-chart-column"></i> Laporan
        </a>

        <a href="peraturan.php">
            <i class="fa-solid fa-book"></i> Peraturan Kos
        </a>
    </div>

    <div class="menu-bawah">
        <a href="logout.php" class="logout">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>
<!-- Main -->
<div class="main">
    <div class="header">
        <h1><i class="fa-solid fa-chart-line"></i> Laporan Keuangan</h1>
        <p>Grafik pemasukan per bulan (Status Lunas)</p>
    </div>

    <div class="card">
        <canvas id="myChart"></canvas>
    </div>
</div>

<script>
const ctx = document.getElementById('myChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($nama_bulan); ?>,
        datasets: [{
            label: 'Total Pemasukan (Rp)',
            data: <?php echo json_encode($total_bulan); ?>,
            backgroundColor: '#174a8b'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

</body>
</html>