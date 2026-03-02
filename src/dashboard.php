<?php
session_start();

// Jika belum login, tendang ke index.php
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: index.php");
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "budihomestay";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$kamar = mysqli_query($conn, "SELECT * FROM kamar");
$total_kamar = mysqli_num_rows($kamar);

$kosong = mysqli_query($conn, "SELECT * FROM kamar WHERE status='Kosong'");
$total_kosong = mysqli_num_rows($kosong);

$penghuni = mysqli_query($conn, "SELECT * FROM penghuni");
$total_penghuni = mysqli_num_rows($penghuni);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Manajemen Kos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* CSS Anda tetap sama seperti di atas */
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #eaf3ff, #f8fbff);
        }

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
            margin-left: 250px;
            padding: 35px;
        }

        .header {
            position: relative;
            background: linear-gradient(90deg, #4da6ff, #2f80ed);
            color: white;
            padding: 30px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 15px 30px rgba(47,128,237,0.25);
            overflow: hidden;
        }

        .header i {
            font-size: 45px;
        }

        .header h1 {
            margin: 0;
            font-weight: 600;
        }

        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }

        .header::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            top: -70px;
            right: -70px;
        }

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
            transition: 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .card i {
            font-size: 40px;
            margin-bottom: 12px;
            color: #2f80ed;
        }

        .card h3 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .card p {
            font-size: 32px;
            font-weight: bold;
            margin-top: 10px;
            color: #0f2f59;
        }

        .card::after {
            content: "";
            position: absolute;
            width: 120px;
            height: 120px;
            background: rgba(47,128,237,0.05);
            border-radius: 50%;
            top: -40px;
            right: -40px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="menu-atas">
        <h2><i class="fa-solid fa-house"></i> Budi Homestay</h2>

        <a href="dashboard.php" class="<?= ($halaman == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>

        <a href="kamar.php" class="<?= ($halaman == 'kamar.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-bed"></i> Data Kamar
        </a>

        <a href="penghuni.php" class="<?= ($halaman == 'penghuni.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Data Penghuni
        </a>

        <a href="pembayaran.php" class="<?= ($halaman == 'pembayaran.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-money-bill-wave"></i> Pembayaran
        </a>

        <a href="laporan.php" class="<?= ($halaman == 'laporan.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-column"></i> Laporan
        </a>
    </div>

    <div class="menu-bawah">
        <a href="logout.php" class="logout">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>

<div class="main">
    <div class="header">
        <i class="fa-solid fa-house"></i>
        <div>
            <h1 style="margin:0;">Dashboard</h1>
            <p style="margin:0;">Selamat Datang, <?php echo $_SESSION['username']; ?> di Budi Homestay</p>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <i class="fa-solid fa-door-open"></i>
            <h3>Total Kamar</h3>
            <p><?php echo $total_kamar; ?></p>
        </div>

        <div class="card">
            <i class="fa-solid fa-check-circle"></i>
            <h3>Kamar Kosong</h3>
            <p><?php echo $total_kosong; ?></p>
        </div>

        <div class="card">
            <i class="fa-solid fa-user"></i>
            <h3>Total Penghuni</h3>
            <p><?php echo $total_penghuni; ?></p>
        </div>
    </div>
</div>

</body>
</html>