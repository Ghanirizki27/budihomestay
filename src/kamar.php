<?php
include "koneksi.php";

session_start();

// Cek apakah sudah login dan apakah rolenya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Jika bukan admin, arahkan balik ke login atau beri pesan error
    header("Location:login.php");
    exit();
}

/* ===============================
   TAMBAH KAMAR
=================================*/
if (isset($_POST['tambah'])) {

    $kode  = mysqli_real_escape_string($conn, $_POST['nomor_kamar']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);

    mysqli_query($conn, "
        INSERT INTO kamar (nomor_kamar, harga, status)
        VALUES ('$kode', '$harga', 'Kosong')
    ");

    header("Location: kamar.php");
    exit;
}

/* ===============================
   HAPUS KAMAR
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
<html>
<head>
<title>Data Kamar - Budi Homestay</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

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

.main.shift {
    margin-left: 250px;
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
    font-size: 20px;
    cursor: pointer;
    padding: 10px;
}

/* ===== CARD ===== */
.card {
    background: white;
    padding: 25px;
    border-radius: 18px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    margin-top: 25px;
}

/* ===== FORM ===== */
input {
    padding: 10px;
    margin: 5px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

button {
    padding: 10px 15px;
    background: #2f80ed;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

/* ===== TABLE ===== */
table {
    width: 100%;
    border-collapse: collapse;
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
    font-weight: bold;
}

.status-terisi {
    color: red;
    font-weight: bold;
}

/* ===== OVERLAY ===== */
.overlay {
    position: fixed;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.4);
    display: none;
    top: 0;
    left: 0;
}

.overlay.active {
    display: block;
}

/* ===== RESPONSIVE ===== */
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

    <div class="menu-bawah">
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>

<!-- MAIN -->
<div class="main" id="main">

    <!-- HEADER -->
    <div class="header">
        <div class="menu-icon" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </div>
        <h2 style="margin-left:15px;"></h2>
    </div>

    <!-- FORM -->
    <div class="card">
        <h3>Tambah Kamar</h3>
        <form method="POST">
            <input type="text" name="nomor_kamar" placeholder="nomor_Kamar" required>
            <input type="number" name="harga" placeholder="Harga" required>
            <button name="tambah">Simpan</button>
        </form>
    </div>

    <!-- TABLE -->
    <div class="card">
        <h3>Daftar Kamar</h3>
        <table>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Harga</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            <?php $no=1; while($row=mysqli_fetch_assoc($dataKamar)){ ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $row['nomor_kamar']; ?></td>
                <td>Rp <?= number_format($row['harga']); ?></td>
                <td>
                    <span class="<?= $row['status']=='Kosong' ? 'status-kosong':'status-terisi'; ?>">
                        <?= $row['status']; ?>
                    </span>
                </td>
                <td>
                    <a href="?hapus=<?= $row['id_kamar']; ?>"
                       onclick="return confirm('Hapus kamar?')"
                       style="color:red;">Hapus</a>
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