<?php
include "koneksi.php";
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

/* =========================
   TAMBAH PEMBAYARAN
========================= */
if (isset($_POST['tambah'])) {

    $penyewa = $_POST['penyewa'];
    $tanggal = $_POST['tanggal'];
    $jumlah  = $_POST['jumlah'];

    mysqli_query($conn, "
        INSERT INTO pembayaran
        (id_penyewa, tanggal_bayar, jumlah, status)
        VALUES
        ('$penyewa', '$tanggal', '$jumlah', 'Divalidasi')
    ");

    mysqli_query($conn, "
        INSERT INTO transaksi_keuangan
        (id_penyewa, tanggal, jenis, jumlah, keterangan)
        VALUES
        ('$penyewa', '$tanggal', 'Pemasukan', '$jumlah', 'Pembayaran sewa kos')
    ");

    header("Location: pembayaran.php");
    exit;
}

/* =========================
   HAPUS
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
   DATA
========================= */
$dataPembayaran = mysqli_query($conn, "
    SELECT pembayaran.*, penyewa.nama, kamar.nomor_kamar
    FROM pembayaran
    JOIN penyewa ON pembayaran.id_penyewa = penyewa.id_penyewa
    LEFT JOIN kamar ON penyewa.id_kamar = kamar.id_kamar
    ORDER BY pembayaran.id_pembayaran DESC
");

$dataPenyewa = mysqli_query($conn, "SELECT * FROM penyewa");
?>

<!DOCTYPE html>
<html>
<head>
<title>Pembayaran - Budi Homestay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

/* ===== GLOBAL ===== */
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

.menu-bawah a {
    background: #0d2c54;
}

/* ===== MAIN ===== */
.main {
    padding: 35px;
    transition: 0.3s;
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

.menu-icon {
    cursor: pointer;
}

/* ===== CARD ===== */
.card {
    background: white;
    padding: 25px;
    border-radius: 18px;
    margin-top: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

/* ===== FORM ===== */
form input, form select {
    padding: 10px;
    margin: 8px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

form button {
    padding: 10px 18px;
    background: #2f80ed;
    color: white;
    border: none;
    border-radius: 8px;
}

/* ===== TABLE ===== */
table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

th {
    background: #f1f5fb;
}

/* ===== OVERLAY ===== */
.overlay {
    position: fixed;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.4);
    display: none;
}

.overlay.active {
    display: block;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px){
    .sidebar {
        width: 70%;
        left: -70%;
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
        <a href="laporan.php"><i class="fa-solid fa-chart-line"></i> Laporan Keuangan</a>
        <a href="peraturan.php"><i class="fa-solid fa-book"></i> Pengumuman</a>
    </div>

    <div class="menu-bawah">
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

<div class="header">
    <div class="menu-icon" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>
    <h2 style="margin-left:15px;"></h2>
</div>

<!-- FORM -->
<div class="card">
    <h3>Tambah Pembayaran</h3>
    <form method="POST">
        <select name="penyewa" required>
            <option value="">Pilih Penyewa</option>
            <?php while($p = mysqli_fetch_assoc($dataPenyewa)) { ?>
                <option value="<?= $p['id_penyewa']; ?>">
                    <?= $p['nama']; ?>
                </option>
            <?php } ?>
        </select>

        <input type="date" name="tanggal" required>
        <input type="number" name="jumlah" placeholder="Jumlah" required>

        <button name="tambah">Simpan</button>
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
            <th>Tanggal</th>
            <th>Jumlah</th>
            <th>Aksi</th>
        </tr>

        <?php $no=1; while($row=mysqli_fetch_assoc($dataPembayaran)){ ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= $row['nama']; ?></td>
            <td><?= $row['nomor_kamar']; ?></td>
            <td><?= $row['tanggal_bayar']; ?></td>
            <td>Rp <?= number_format($row['jumlah']); ?></td>
            <td>
                <a href="?hapus=<?= $row['id_pembayaran']; ?>" style="color:red;">Hapus</a>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>

</div>

<script>
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("overlay").classList.toggle("active");
}

function closeSidebar(){
    document.getElementById("sidebar").classList.remove("active");
    document.getElementById("overlay").classList.remove("active");
}
</script>

</body>
</html>