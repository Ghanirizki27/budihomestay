<?php
include "koneksi.php";

// Tambah kamar
if(isset($_POST['tambah'])){
    $nomor = $_POST['nomor_kamar'];
    $harga = $_POST['harga'];

    mysqli_query($conn, "INSERT INTO kamar (nomor_kamar, harga, status) 
                         VALUES ('$nomor', '$harga', 'Kosong')");
    header("Location: kamar.php");
}

// Hapus kamar
if(isset($_GET['hapus'])){
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kamar WHERE id=$id");
    header("Location: kamar.php");
}

$data = mysqli_query($conn, "SELECT * FROM kamar");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Kamar</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            font-family: Arial;
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
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
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

        .card {
            background: white;
            padding: 20px;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        input, button {
            padding: 8px;
            margin: 5px;
        }

        button {
            background-color: #4da6ff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #3399ff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        table th {
            background-color: #4da6ff;
            color: white;
        }

        .hapus {
            background-color: red;
            padding: 5px 10px;
            color: white;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2><i class="fa-solid fa-house"></i> Budi Homestay</h2>

    <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="kamar.php"><i class="fa-solid fa-bed"></i> Data Kamar</a>
    <a href="#"><i class="fa-solid fa-users"></i> Data Penghuni</a>
    <a href="#"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a>
    <a href="#"><i class="fa-solid fa-file-contract"></i> Peraturan Kos</a>
</div>

<div class="main">
    <div class="header">
        <i class="fa-solid fa-bed"></i>
        <h2>Data Kamar</h2>
    </div>

    <div class="card">
        <h3>Tambah Kamar</h3>
        <form method="POST">
            <input type="text" name="nomor_kamar" placeholder="Nomor Kamar" required>
            <input type="number" name="harga" placeholder="Harga" required>
            <button type="submit" name="tambah">
                <i class="fa-solid fa-plus"></i> Tambah
            </button>
        </form>
    </div>

    <div class="card">
        <h3>Daftar Kamar</h3>
        <table>
            <tr>
                <th>No</th>
                <th>Nomor Kamar</th>
                <th>Harga</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            <?php
            $no = 1;
            while($row = mysqli_fetch_assoc($data)){
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $row['nomor_kamar']; ?></td>
                <td>Rp <?= number_format($row['harga']); ?></td>
                <td><?= $row['status']; ?></td>
                <td>
                    <a class="hapus" href="?hapus=<?= $row['id']; ?>" 
                       onclick="return confirm('Yakin hapus kamar?')">
                       Hapus
                    </a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

</div>

</body>
</html>