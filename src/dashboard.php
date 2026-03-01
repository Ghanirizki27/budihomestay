<?php
include "koneksi.php";

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

    <!-- Font Awesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f9ff;
        }

        .sidebar {
            width: 230px;
            height: 100vh;
            background-color: #4da6ff;
            position: fixed;
            padding-top: 20px;
        }

        .sidebar h2 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            gap: 10px;
        }

        .sidebar a:hover {
            background-color: #3399ff;
        }

        .main {
            margin-left: 230px;
            padding: 20px;
        }

        .header {
            background-color: #4da6ff;
            color: white;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cards {
            margin-top: 20px;
        }

        .card {
            display: inline-block;
            width: 250px;
            padding: 20px;
            margin: 10px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .card i {
            font-size: 35px;
            color: #4da6ff;
            margin-bottom: 10px;
        }

        .card h3 {
            margin: 0;
        }

        .card p {
            font-size: 26px;
            font-weight: bold;
            margin: 5px 0 0;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2><i class="fa-solid fa-house"></i> Budi Homestay</h2>

    <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="#"><i class="fa-solid fa-bed"></i> Data Kamar</a>
    <a href="#"><i class="fa-solid fa-users"></i> Data Penghuni</a>
    <a href="#"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a>
    <a href="#"><i class="fa-solid fa-file-contract"></i> Peraturan Kos</a>
    <a href="../index.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
    <div class="header">
        <i class="fa-solid fa-house"></i>
        <div>
            <h1 style="margin:0;">Dashboard</h1>
            <p style="margin:0;">Selamat Datang di Sistem Manajemen Kos</p>
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