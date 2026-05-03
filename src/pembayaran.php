<?php
include "koneksi.php";
include_once "admin_nav.php";
include_once "db_migrations.php";
include_once "auth.php";
session_start();
requireRole('admin');

ensureAppSchema($conn);

if (isset($_POST['bayar'])) {
    $penyewa = (int) $_POST['id_penyewa'];

    $ambilPenyewa = mysqli_query($conn, "
        SELECT penyewa.*, kamar.nomor_kamar, kamar.harga
        FROM penyewa
        LEFT JOIN kamar ON penyewa.id_kamar = kamar.id_kamar
        WHERE penyewa.id_penyewa = '$penyewa'
    ");

    if ($dataPenyewaBayar = mysqli_fetch_assoc($ambilPenyewa)) {
        $lastPaymentQuery = mysqli_query($conn, "
            SELECT tanggal_bayar
            FROM pembayaran
            WHERE id_penyewa = '$penyewa' AND status='Divalidasi'
            ORDER BY tanggal_bayar DESC
            LIMIT 1
        ");

        $lastPayment = mysqli_fetch_assoc($lastPaymentQuery);
        $baseDate = $lastPayment['tanggal_bayar'] ?? $dataPenyewaBayar['tanggal_masuk'];
        $jatuhTempo = date('Y-m-d', strtotime($baseDate . ' +1 month'));
        $tanggalBayar = date('Y-m-d');
        $jumlah = (int) $dataPenyewaBayar['harga'];

        if ($tanggalBayar >= $jatuhTempo) {
            mysqli_query($conn, "
                INSERT INTO pembayaran
                (id_penyewa, tanggal_bayar, jumlah, metode_pembayaran, jatuh_tempo, status)
                VALUES
                ('$penyewa', '$tanggalBayar', '$jumlah', NULL, '$jatuhTempo', 'Divalidasi')
            ");

            mysqli_query($conn, "
                INSERT INTO transaksi_keuangan
                (id_penyewa, tanggal, jenis, jumlah, keterangan)
                VALUES
                ('$penyewa', '$tanggalBayar', 'Pemasukan', '$jumlah', 'Pembayaran sewa kos kamar {$dataPenyewaBayar['nomor_kamar']}')
            ");

            $_SESSION['flash_pembayaran'] = 'Pembayaran berhasil divalidasi. Tagihan berikutnya sudah diperbarui otomatis.';
        } else {
            $_SESSION['flash_pembayaran'] = 'Tagihan belum jatuh tempo, jadi pembayaran belum bisa diproses lagi.';
        }
    }

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

$tenantData = mysqli_query($conn, "
    SELECT 
        penyewa.*,
        kamar.nomor_kamar,
        kamar.harga,
        (
            SELECT MAX(pembayaran.tanggal_bayar)
            FROM pembayaran
            WHERE pembayaran.id_penyewa = penyewa.id_penyewa
            AND pembayaran.status='Divalidasi'
        ) AS pembayaran_terakhir
    FROM penyewa
    LEFT JOIN kamar ON penyewa.id_kamar = kamar.id_kamar
    WHERE penyewa.status_sewa='Aktif'
    ORDER BY penyewa.nama ASC
");

$flashPembayaran = $_SESSION['flash_pembayaran'] ?? null;
unset($_SESSION['flash_pembayaran']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
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

/* ===== MAIN ===== */
.main {
    margin-left: 0;
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
    justify-content: space-between;
}

.menu-icon {
    cursor: pointer;
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

.flash-success {
    margin-top: 20px;
    padding: 14px 18px;
    border-radius: 12px;
    background: #eaf8ee;
    color: #1d7e45;
    font-weight: 600;
}

.status-note {
    color: #6a7a92;
    font-weight: 600;
}

.status-tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 13px;
    font-weight: 700;
}

.status-lunas {
    background: #eaf8ee;
    color: #1d7e45;
}

.status-warning {
    background: #fff3db;
    color: #a86900;
}

.status-danger {
    background: #ffe7e7;
    color: #c23c3c;
}

.pay-button {
    padding: 10px 16px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(90deg, #4da6ff, #2f80ed);
    color: white;
    font-weight: 700;
    cursor: pointer;
}

.pay-button:hover {
    opacity: 0.92;
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

    .main.shift {
        margin-left: 0;
    }
}

</style>
</head>

<body>

<!-- SIDEBAR -->
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


<!-- MAIN -->
<div class="main" id="main">

<div class="header">
    <div style="display:flex; align-items:center; gap:14px;">
        <div class="menu-icon" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </div>
        <div class="header-title">
            <h2>Pembayaran Sewa</h2>
            <p>Status aktif sewa, jatuh tempo, dan riwayat pembayaran otomatis</p>
        </div>
    </div>
</div>

<?php if ($flashPembayaran): ?>
    <div class="flash-success"><?= htmlspecialchars($flashPembayaran); ?></div>
<?php endif; ?>

<div class="card">
    <h3>Status Tagihan Penyewa</h3>
    <table>
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Kamar</th>
            <th>Tanggal Masuk</th>
            <th>Jatuh Tempo</th>
            <th>Tagihan</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>

        <?php $no = 1; while ($tenant = mysqli_fetch_assoc($tenantData)) {
            $tanggalAcuan = $tenant['pembayaran_terakhir'] ?: $tenant['tanggal_masuk'];
            $jatuhTempo = date('Y-m-d', strtotime($tanggalAcuan . ' +1 month'));
            $hariTerlambat = max(0, (int) floor((strtotime(date('Y-m-d')) - strtotime($jatuhTempo)) / 86400));
            $statusLabel = 'Akan Jatuh Tempo';
            $statusClass = 'status-warning';
            $bolehBayar = $jatuhTempo <= date('Y-m-d');

            if ($hariTerlambat > 0) {
                $statusLabel = 'Terlambat ' . $hariTerlambat . ' hari';
                $statusClass = 'status-danger';
            } elseif ($jatuhTempo > date('Y-m-d')) {
                $statusLabel = 'Aktif';
                $statusClass = 'status-lunas';
            }
        ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($tenant['nama']); ?></td>
            <td><?= htmlspecialchars($tenant['nomor_kamar'] ?: '-'); ?></td>
            <td><?= htmlspecialchars($tenant['tanggal_masuk']); ?></td>
            <td><?= htmlspecialchars($jatuhTempo); ?></td>
            <td>Rp <?= number_format((int) $tenant['harga']); ?></td>
            <td><span class="status-tag <?= $statusClass; ?>"><?= htmlspecialchars($statusLabel); ?></span></td>
            <td>
                <?php if ($bolehBayar): ?>
                    <form method="POST" onsubmit="return confirm('Konfirmasi pembayaran penyewa ini?');">
                        <input type="hidden" name="id_penyewa" value="<?= (int) $tenant['id_penyewa']; ?>">
                        <button class="pay-button" type="submit" name="bayar">Bayar Sekarang</button>
                    </form>
                <?php else: ?>
                    <span class="status-note">Menunggu jatuh tempo</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php } ?>
    </table>
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
            <th>Jatuh Tempo</th>
            <th>Jumlah</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>

        <?php $no=1; while($row=mysqli_fetch_assoc($dataPembayaran)){ ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= $row['nama']; ?></td>
            <td><?= $row['nomor_kamar']; ?></td>
            <td><?= $row['tanggal_bayar']; ?></td>
            <td><?= $row['jatuh_tempo'] ?: '-'; ?></td>
            <td>Rp <?= number_format($row['jumlah']); ?></td>
            <td><?= $row['status']; ?></td>
            <td>
                <a href="?hapus=<?= $row['id_pembayaran']; ?>" style="color:red;">Hapus</a>
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

function toggleSidebar(){
    sidebar.classList.toggle("active");

    if (window.innerWidth <= 768) {
        overlay.classList.toggle("active");
    } else {
        overlay.classList.remove("active");
    }

    syncSidebarLayout();
}

function closeSidebar(){
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
    syncSidebarLayout();
}

window.addEventListener("resize", syncSidebarLayout);
syncSidebarLayout();
</script>

</body>
</html>
