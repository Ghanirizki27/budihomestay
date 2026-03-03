<?php
include "koneksi.php";
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: index.php");
    exit;
}

/* =========================
   TAMBAH PEMBAYARAN
========================= */
if (isset($_POST['tambah'])) {

    $penghuni = $_POST['penghuni'];
    $bulan    = $_POST['bulan'];
    $tahun    = $_POST['tahun'];
    $tanggal  = $_POST['tanggal'];
    $jumlah   = $_POST['jumlah'];
    $status   = $_POST['status'];

    mysqli_query($conn, "
        INSERT INTO pembayaran
        (id_penghuni, bulan, tahun, tanggal_bayar, jumlah_bayar, status)
        VALUES
        ('$penghuni', '$bulan', '$tahun', '$tanggal', '$jumlah', '$status')
    ");

    header("Location: pembayaran.php");
    exit;
}

/* =========================
   HAPUS PEMBAYARAN
========================= */
if (isset($_GET['hapus'])) {

    $id = $_GET['hapus'];

    mysqli_query($conn, "
        DELETE FROM pembayaran
        WHERE id_pembayaran='$id'
    ");

    header("Location: pembayaran.php");
    exit;
}

/* =========================
   AMBIL DATA
========================= */
$dataPembayaran = mysqli_query($conn, "
    SELECT pembayaran.*, penghuni.nama_penghuni, kamar.kode_kamar
    FROM pembayaran
    JOIN penghuni ON pembayaran.id_penghuni = penghuni.id_penghuni
    JOIN kamar ON penghuni.id_kamar = kamar.id_kamar
    ORDER BY pembayaran.id_pembayaran DESC
");

$dataPenghuni = mysqli_query($conn, "
    SELECT *
    FROM penghuni
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Pembayaran - Budi Homestay</title>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
}

.sidebar a:hover {
    background: rgba(255,255,255,0.08);
    padding-left: 28px;
}

.menu-bawah a {
    background: #0d2c54;
}

/* ================= MAIN ================= */
.main {
    margin-left: 250px;
    padding: 35px;
}

/* ================= HEADER ================= */
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
    margin-bottom: 30px;
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
    border-radius: 18px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

/* ================= FORM ================= */
form input,
form select {
    padding: 10px;
    margin: 8px;
    border: 1px solid #ccc;
    border-radius: 8px;
}

form button {
    padding: 10px 18px;
    background: #2f80ed;
    color: white;
    border: none;
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
    text-align: center;
    border-bottom: 1px solid #eee;
}

th {
    background: #f1f5fb;
}

.status-lunas {
    background: #d4edda;
    color: #155724;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.status-belum {
    background: #f8d7da;
    color: #721c24;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div>
        <h2><i class="fa-solid fa-house"></i> Budi Homestay</h2>
        <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="kamar.php"><i class="fa-solid fa-bed"></i> Data Kamar</a>
        <a href="penghuni.php"><i class="fa-solid fa-users"></i> Data Penghuni</a>
        <a href="pembayaran.php"><i class="fa-solid fa-money-bill-wave"></i> Pembayaran</a>
        <a href="laporan.php"><i class="fa-solid fa-chart-line"></i> Laporan</a>
        <a href="peraturan.php"><i class="fa-solid fa-list-check"></i> Peraturan Kos</a>
    </div>

    <div class="menu-bawah">
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <!-- HEADER -->
    <div class="header">
        <i class="fa-solid fa-money-bill-wave"></i>
        <div>
            <h1>Data Pembayaran</h1>
            <p>Kelola pembayaran sewa kamar</p>
        </div>
    </div>

    <!-- FORM -->
    <div class="card">
        <h3>Tambah Pembayaran</h3>
        <form method="POST">
            <select name="penghuni" required>
                <option value="">Pilih Penghuni</option>
                <?php while($p = mysqli_fetch_assoc($dataPenghuni)) { ?>
                    <option value="<?= $p['id_penghuni']; ?>">
                        <?= $p['nama_penghuni']; ?>
                    </option>
                <?php } ?>
            </select>

            <input type="text" name="bulan" placeholder="Bulan (Januari)" required>
            <input type="number" name="tahun" placeholder="Tahun" required>
            <input type="date" name="tanggal" required>
            <input type="number" name="jumlah" placeholder="Jumlah Bayar" required>

            <select name="status">
                <option value="Lunas">Lunas</option>
                <option value="Belum Lunas">Belum Lunas</option>
            </select>

            <button type="submit" name="tambah">Simpan</button>
        </form>
    </div>

    <!-- TABLE -->
    <div class="card">
        <h3>Riwayat Pembayaran</h3>
        <table>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Kamar</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>Tanggal Bayar</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            <?php $no = 1; while($row = mysqli_fetch_assoc($dataPembayaran)) { ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $row['nama_penghuni']; ?></td>
                <td><?= $row['kode_kamar']; ?></td>
                <td><?= $row['bulan']; ?></td>
                <td><?= $row['tahun']; ?></td>
                <td><?= $row['tanggal_bayar']; ?></td>
                <td>Rp <?= number_format($row['jumlah_bayar'],0,',','.'); ?></td>
                <td>
                    <?php if($row['status']=="Lunas"){ ?>
                        <span class="status-lunas">Lunas</span>
                    <?php } else { ?>
                        <span class="status-belum">Belum</span>
                    <?php } ?>
                </td>
                <td>
                    <a href="?hapus=<?= $row['id_pembayaran']; ?>"
                       onclick="return confirm('Yakin hapus data?')"
                       style="color:red;">Hapus</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

</div>
</body>
</html>