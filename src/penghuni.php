<?php
include "koneksi.php";
session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: index.php");
    exit;
}

/* =========================
   TAMBAH PENGHUNI
========================= */
if (isset($_POST['tambah'])) {

    $nama   = $_POST['nama'];
    $ktp    = $_POST['ktp'];
    $hp     = $_POST['hp'];
    $alamat = $_POST['alamat'];
    $kamar  = $_POST['kamar'];
    $tgl    = $_POST['tanggal'];

    mysqli_query($conn, "
        INSERT INTO penghuni
        (nama_penghuni, no_ktp, no_hp, alamat, id_kamar, tanggal_masuk)
        VALUES
        ('$nama', '$ktp', '$hp', '$alamat', '$kamar', '$tgl')
    ");

    mysqli_query($conn, "
        UPDATE kamar
        SET status='Terisi'
        WHERE id_kamar='$kamar'
    ");

    header("Location: penghuni.php");
    exit;
}

/* =========================
   HAPUS PENGHUNI
========================= */
if (isset($_GET['hapus'])) {

    $id = $_GET['hapus'];

    $ambil = mysqli_query($conn, "
        SELECT id_kamar
        FROM penghuni
        WHERE id_penghuni='$id'
    ");

    $dataKamar = mysqli_fetch_assoc($ambil);
    $id_kamar  = $dataKamar['id_kamar'];

    mysqli_query($conn, "
        DELETE FROM penghuni
        WHERE id_penghuni='$id'
    ");

    mysqli_query($conn, "
        UPDATE kamar
        SET status='Kosong'
        WHERE id_kamar='$id_kamar'
    ");

    header("Location: penghuni.php");
    exit;
}

/* =========================
   AMBIL DATA
========================= */
$dataPenghuni = mysqli_query($conn, "
    SELECT penghuni.*, kamar.kode_kamar
    FROM penghuni
    JOIN kamar ON penghuni.id_kamar = kamar.id_kamar
");

$dataKamarKosong = mysqli_query($conn, "
    SELECT *
    FROM kamar
    WHERE status='Kosong'
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Penghuni - Budi Homestay</title>
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

/* ================= HEADER BIRU ================= */
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
        <i class="fa-solid fa-users"></i>
        <div>
            <h1>Data Penghuni</h1>
            <p>Kelola data penghuni homestay</p>
        </div>
    </div>

    <!-- FORM -->
    <div class="card">
        <h3>Tambah Penghuni</h3>
        <form method="POST">
            <input type="text" name="nama" placeholder="Nama Penghuni" required>
            <input type="text" name="ktp" placeholder="No KTP" required>
            <input type="text" name="hp" placeholder="No HP" required>
            <input type="text" name="alamat" placeholder="Alamat" required>

            <select name="kamar" required>
                <option value="">Pilih Kamar Kosong</option>
                <?php while($k = mysqli_fetch_assoc($dataKamarKosong)) { ?>
                    <option value="<?= $k['id_kamar']; ?>">
                        <?= $k['kode_kamar']; ?>
                    </option>
                <?php } ?>
            </select>

            <input type="date" name="tanggal" required>
            <button type="submit" name="tambah">Simpan</button>
        </form>
    </div>

    <!-- TABLE -->
    <div class="card">
        <h3>Daftar Penghuni</h3>
        <table>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>No KTP</th>
                <th>No HP</th>
                <th>Alamat</th>
                <th>Kamar</th>
                <th>Tanggal Masuk</th>
                <th>Aksi</th>
            </tr>

            <?php $no = 1; while($row = mysqli_fetch_assoc($dataPenghuni)) { ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $row['nama_penghuni']; ?></td>
                <td><?= $row['no_ktp']; ?></td>
                <td><?= $row['no_hp']; ?></td>
                <td><?= $row['alamat']; ?></td>
                <td><?= $row['kode_kamar']; ?></td>
                <td><?= $row['tanggal_masuk']; ?></td>
                <td>
                    <a href="?hapus=<?= $row['id_penghuni']; ?>"
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