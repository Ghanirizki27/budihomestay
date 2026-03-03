<?php
include "koneksi.php";
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: index.php");
    exit;
}

/* ===============================
   PROSES TAMBAH KAMAR
=================================*/
if (isset($_POST['tambah'])) {

    $kode  = mysqli_real_escape_string($conn, $_POST['kode_kamar']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);

    mysqli_query($conn, "
        INSERT INTO kamar (kode_kamar, harga, status)
        VALUES ('$kode', '$harga', 'Kosong')
    ");

    header("Location: kamar.php");
    exit;
}

/* ===============================
   PROSES HAPUS KAMAR
=================================*/
if (isset($_GET['hapus'])) {

    $id = mysqli_real_escape_string($conn, $_GET['hapus']);

    mysqli_query($conn, "
        DELETE FROM kamar WHERE id_kamar='$id'
    ");

    header("Location: kamar.php");
    exit;
}

$dataKamar = mysqli_query($conn, "SELECT * FROM kamar");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Kamar - Budi Homestay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

/* ================= GLOBAL ================= */
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #eaf3ff, #f8fbff);
}

/* ================= SIDEBAR ================= */
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

.sidebar a.active {
    background: rgba(255,255,255,0.15);
    padding-left: 28px;
}

.sidebar-footer a {
    background: #0d2c54;
}

.sidebar-footer a:hover {
    background: #123d75;
}

/* ================= MAIN ================= */
.main {
    margin-left: 250px;
    padding: 35px;
}

/* ================= HEADER (SAMA SEPERTI HALAMAN LAIN) ================= */
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
    margin-bottom: 40px;
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

/* ================= CARD ================= */
.card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

/* ================= FORM ================= */
input {
    padding: 10px;
    margin: 5px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

button {
    background: #2f80ed;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
}

/* ================= TABLE ================= */
table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #eee;
    text-align: center;
}

th {
    background: #f1f5fb;
}

.status-kosong {
    color: green;
    font-weight: 600;
}

.status-terisi {
    color: red;
    font-weight: 600;
}

.hapus-btn {
    color: #dc3545;
    text-decoration: none;
    font-weight: 600;
}

</style>
</head>

<body>

<!-- ================= SIDEBAR ================= -->
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
        <a href="Peraturan kost.php" class="<?= ($halaman == 'Peraturan kost.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-column"></i> Peraturan Kost
        </a>
    </div>

    <div class="menu-bawah">
        <a href="logout.php" class="logout">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>

<!-- ================= MAIN ================= -->
<div class="main">

    <div class="header">
        <i class="fa-solid fa-bed"></i>
        <div>
            <h1>Data Kamar</h1>
            <p>Kelola data kamar homestay</p>
        </div>
    </div>

    <div class="card">
        <h3>Tambah Kamar</h3>
        <form method="POST">
            <input type="text" name="kode_kamar" placeholder="Kode Kamar (A01)" required>
            <input type="number" name="harga" placeholder="Harga Sewa" required>
            <button type="submit" name="tambah">Simpan</button>
        </form>
    </div>

    <div class="card">
        <h3>Daftar Kamar</h3>
        <table>
            <tr>
                <th>No</th>
                <th>Kode Kamar</th>
                <th>Harga</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            <?php $no=1; while($row=mysqli_fetch_assoc($dataKamar)){ ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $row['kode_kamar']; ?></td>
                <td>Rp <?= number_format($row['harga'],0,',','.'); ?></td>
                <td>
                    <span class="<?= $row['status']=='Kosong' ? 'status-kosong' : 'status-terisi'; ?>">
                        <?= $row['status']; ?>
                    </span>
                </td>
                <td>
                    <a class="hapus-btn"
                       href="?hapus=<?= $row['id_kamar']; ?>"
                       onclick="return confirm('Yakin ingin menghapus kamar ini?')">
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