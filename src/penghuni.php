<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

include "koneksi.php";

/* =========================
   TAMBAH
========================= */
if (isset($_POST['tambah'])) {

    $nama   = $_POST['nama'];
    $hp     = $_POST['hp'];
    $kamar  = $_POST['kamar'];
    $tgl    = $_POST['tanggal'];

    mysqli_query($conn, "
        INSERT INTO penyewa (nama, nomor_telepon, tanggal_masuk, id_kamar)
        VALUES ('$nama','$hp','$tgl','$kamar')
    ");

    mysqli_query($conn, "
        UPDATE kamar SET status='Ditempati'
        WHERE id_kamar='$kamar'
    ");

    header("Location: penghuni.php");
    exit;
}

/* =========================
   HAPUS
========================= */
if (isset($_GET['hapus'])) {

    $id = $_GET['hapus'];

    $ambil = mysqli_query($conn, "
        SELECT id_kamar FROM penyewa WHERE id_penyewa='$id'
    ");
    $data = mysqli_fetch_assoc($ambil);

    mysqli_query($conn, "DELETE FROM penyewa WHERE id_penyewa='$id'");

    mysqli_query($conn, "
        UPDATE kamar SET status='Kosong'
        WHERE id_kamar='".$data['id_kamar']."'
    ");

    header("Location: penghuni.php");
    exit;
}

/* =========================
   DATA
========================= */
$dataPenyewa = mysqli_query($conn, "
    SELECT penyewa.*, kamar.nomor_kamar
    FROM penyewa
    LEFT JOIN kamar ON penyewa.id_kamar = kamar.id_kamar
");

$dataKamarKosong = mysqli_query($conn, "
    SELECT * FROM kamar WHERE status='Kosong'
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Data Penyewa</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #eaf3ff, #f8fbff);
}

/* SIDEBAR */
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
}

.sidebar a {
    display: flex;
    gap: 12px;
    padding: 14px 20px;
    color: #dbe9ff;
    text-decoration: none;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.1);
}

/* MAIN */
.main {
    padding: 35px;
}

/* HEADER */
.header {
    background: linear-gradient(90deg, #4da6ff, #2f80ed);
    color: white;
    padding: 20px;
    border-radius: 18px;
    display: flex;
    align-items: center;
}

/* MENU ICON */
.menu-icon {
    cursor: pointer;
    font-size: 20px;
    margin-right: auto;
}

/* CARD */
.card {
    margin-top: 30px;
    padding: 25px;
    border-radius: 18px;
    background: white;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

/* FORM */
input, select {
    padding: 10px;
    margin: 8px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 12px;
    text-align: center;
}

th {
    background: #f1f5fb;
}

/* OVERLAY */
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

/* RESPONSIVE */
@media (max-width: 768px) {
    .sidebar {
        width: 65%;
        left: -65%;
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
        <a href="pembayaran.php"><i class="fa-solid fa-money-bill"></i> Pembayaran</a>
        <a href="laporan.php"><i class="fa-solid fa-chart-line"></i> Laporan Keuangan</a>
        <a href="peraturan.php"><i class="fa-solid fa-book"></i> Pengumuman</a>
    </div>

    <div>
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <!-- HEADER -->
    <div class="header">
        <div class="menu-icon" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </div>
        <h3 style="margin-left:15px;"></h3>
    </div>

    <!-- FORM -->
    <div class="card">
        <h3>Tambah Penyewa</h3>
        <form method="POST">
            <input type="text" name="nama" placeholder="Nama" required>
            <input type="text" name="hp" placeholder="No HP" required>

            <select name="kamar" required>
                <option value="">Pilih Kamar</option>
                <?php while($k = mysqli_fetch_assoc($dataKamarKosong)) { ?>
                    <option value="<?= $k['id_kamar']; ?>">
                        <?= $k['nomor_kamar']; ?>
                    </option>
                <?php } ?>
            </select>

            <input type="date" name="tanggal" required>

            <button name="tambah">Tambah</button>
        </form>
    </div>

    <!-- TABLE -->
    <div class="card">
        <h3>Data Penyewa</h3>

        <table>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>No HP</th>
                <th>Kamar</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>

            <?php $no=1; while($row = mysqli_fetch_assoc($dataPenyewa)) { ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $row['nama']; ?></td>
                <td><?= $row['nomor_telepon']; ?></td>
                <td><?= $row['nomor_kamar']; ?></td>
                <td><?= $row['tanggal_masuk']; ?></td>
                <td>
                    <a href="?hapus=<?= $row['id_penyewa']; ?>" onclick="return confirm('Hapus?')" style="color:red;">
                        Hapus
                    </a>
                </td>
            </tr>
            <?php } ?>
        </table>
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
</script>

</body>
</html>