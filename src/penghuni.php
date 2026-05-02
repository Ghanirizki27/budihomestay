<?php
session_start();
include_once "auth.php";
requireRole('admin');
include "koneksi.php";
include_once "admin_nav.php";
include_once "db_migrations.php";

ensureAppSchema($conn);

/* =========================
   HAPUS
========================= */
if (isset($_GET['hapus'])) {

    $id = $_GET['hapus'];

    mysqli_query($conn, "DELETE FROM admin WHERE id_penyewa='$id' AND role='penyewa'");

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
    SELECT penyewa.*, kamar.nomor_kamar, kamar.harga, admin.username
    FROM penyewa
    LEFT JOIN kamar ON penyewa.id_kamar = kamar.id_kamar
    LEFT JOIN admin ON admin.id_penyewa = penyewa.id_penyewa AND admin.role='penyewa'
    ORDER BY penyewa.id_penyewa DESC
");

$flashPenyewa = $_SESSION['flash_penyewa'] ?? null;
unset($_SESSION['flash_penyewa']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
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

.sidebar a.active {
    background: rgba(255,255,255,0.14);
    color: #ffffff;
    margin: 6px 12px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 14px;
    backdrop-filter: blur(6px);
}

 .menu-bawah a {
    background: #0d2c54;
}

.menu-bawah a.active {
    margin: 0;
    border-radius: 0;
    border: none;
}

/* MAIN */
.main {
    margin-left: 0;
    padding: 35px;
    transition: 0.3s;
}

.main.shift {
    margin-left: 250px;
}

/* HEADER */
.header {
    background: linear-gradient(90deg, #4da6ff, #2f80ed);
    color: white;
    padding: 20px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* MENU ICON */
.menu-icon {
    cursor: pointer;
    font-size: 20px;
    padding: 10px;
    border-radius: 10px;
}

.menu-icon:hover {
    background: rgba(255,255,255,0.18);
}

.header-title h2,
.header-title p {
    margin: 0;
}

.header-title p {
    margin-top: 4px;
    opacity: 0.9;
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
    padding: 12px 14px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

textarea {
    padding: 12px 14px;
    border-radius: 8px;
    border: 1px solid #ccc;
    resize: vertical;
    min-height: 96px;
    font-family: inherit;
}

.button-primary {
    padding: 12px 18px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(90deg, #4da6ff, #2f80ed);
    color: white;
    font-weight: 700;
    cursor: pointer;
}

.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}

.toolbar h3,
.toolbar p {
    margin: 0;
}

.toolbar p {
    margin-top: 4px;
    color: #567;
}

.flash-success {
    margin-top: 20px;
    padding: 14px 18px;
    border-radius: 12px;
    background: #eaf8ee;
    color: #1d7e45;
    font-weight: 600;
}

.status-pill {
    display: inline-flex;
    padding: 6px 12px;
    border-radius: 999px;
    background: #eaf7ee;
    color: #1f8f4d;
    font-weight: 600;
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

    .main.shift {
        margin-left: 0;
    }
}

/* MODAL */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
}

.modal-content {
    margin: auto;
    display: block;
    width: 80%;
    max-width: 700px;
    max-height: 80%;
}

.close {
    position: absolute;
    top: 15px;
    right: 35px;
    color: #f1f1f1;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: #bbb;
    text-decoration: none;
}

#ktpImage {
    width: 100%;
    height: auto;
}
</style>
</head>

<body>

<?php renderAdminSidebar('penghuni.php'); ?>

<!-- MAIN -->
<div class="main" id="main">

    <!-- HEADER -->
    <div class="header">
        <div style="display:flex; align-items:center; gap:14px;">
            <div class="menu-icon" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </div>
            <div class="header-title">
                <h2>Data Penyewa</h2>
                <p>Pendaftaran calon penyewa dan pengelolaan data sewa</p>
            </div>
        </div>
    </div>

    <?php if ($flashPenyewa): ?>
        <div class="flash-success"><?= htmlspecialchars($flashPenyewa); ?></div>
    <?php endif; ?>

    <!-- TABLE -->
    <div class="card">
        <div class="toolbar">
            <div>
                <h3>Data Penyewa</h3>
                <p>Klik tombol tambah untuk membuka halaman formulir penyewa baru.</p>
            </div>
            <a class="button-primary" href="tambah_penyewa.php">
                <i class="fa-solid fa-user-plus"></i> Tambah Penyewa
            </a>
        </div>

        <table>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>KTP</th>
                <th>File KTP</th>
                <th>No HP</th>
                <th>Username Login</th>
                <th>Kamar</th>
                <th>Tanggal Masuk</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            <?php $no=1; while($row = mysqli_fetch_assoc($dataPenyewa)) { ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($row['nama']); ?></td>
                <td><?= htmlspecialchars($row['nomor_ktp'] ?: '-'); ?></td>
                <td>
                    <?php if (!empty($row['foto_ktp'])): ?>
                        <button onclick="showKTP('<?= htmlspecialchars($row['foto_ktp']); ?>')" style="padding: 6px 12px; border: none; border-radius: 6px; background: #4da6ff; color: white; cursor: pointer;">Lihat KTP</button>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['nomor_telepon']); ?></td>
                <td><?= htmlspecialchars($row['username'] ?: '-'); ?></td>
                <td><?= htmlspecialchars($row['nomor_kamar'] ?: '-'); ?></td>
                <td><?= htmlspecialchars($row['tanggal_masuk']); ?></td>
                <td><span class="status-pill"><?= htmlspecialchars($row['status_sewa']); ?></span></td>
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
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const main = document.getElementById("main");

function syncSidebarLayout() {
    if (window.innerWidth > 768 && sidebar.classList.contains("active")) {
        main.classList.add("shift");
        overlay.classList.remove("active");
        return;
    }

    main.classList.remove("shift");
}

function toggleSidebar() {
    sidebar.classList.toggle("active");

    if (window.innerWidth <= 768) {
        overlay.classList.toggle("active");
    } else {
        overlay.classList.remove("active");
    }

    syncSidebarLayout();
}

function closeSidebar() {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
    syncSidebarLayout();
}

function showKTP(src) {
    document.getElementById('ktpImage').src = src;
    document.getElementById('ktpModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('ktpModal').style.display = 'none';
}

window.addEventListener("resize", syncSidebarLayout);
syncSidebarLayout();
</script>

<!-- Modal for KTP -->
<div id="ktpModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="ktpImage" src="" alt="KTP">
</div>

</body>
</html>
