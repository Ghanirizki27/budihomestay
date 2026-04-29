<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "admin_nav.php";

requireRole('admin');
ensureAppSchema($conn);

if (isset($_POST['tambah_pengeluaran'])) {
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jumlah = (int) $_POST['jumlah'];
    $keterangan = mysqli_real_escape_string($conn, trim($_POST['keterangan']));

    mysqli_query($conn, "
        INSERT INTO transaksi_keuangan (tanggal, jenis, jumlah, keterangan)
        VALUES ('$tanggal', 'Pengeluaran', '$jumlah', '$keterangan')
    ");

    header("Location: laporan.php");
    exit;
}

$summaryQuery = mysqli_query($conn, "
    SELECT
        SUM(CASE WHEN jenis='Pemasukan' THEN jumlah ELSE 0 END) AS total_pemasukan,
        SUM(CASE WHEN jenis='Pengeluaran' THEN jumlah ELSE 0 END) AS total_pengeluaran
    FROM transaksi_keuangan
");
$summary = mysqli_fetch_assoc($summaryQuery);

$bulan = [1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "Mei", 6 => "Jun", 7 => "Jul", 8 => "Agu", 9 => "Sep", 10 => "Okt", 11 => "Nov", 12 => "Des"];
$pemasukanPerBulan = array_fill(1, 12, 0);
$pengeluaranPerBulan = array_fill(1, 12, 0);

$chartQuery = mysqli_query($conn, "
    SELECT MONTH(tanggal) AS bulan, jenis, SUM(jumlah) AS total
    FROM transaksi_keuangan
    WHERE YEAR(tanggal) = YEAR(CURDATE())
    GROUP BY MONTH(tanggal), jenis
");
while ($row = mysqli_fetch_assoc($chartQuery)) {
    $index = (int) $row['bulan'];
    if ($row['jenis'] === 'Pemasukan') {
        $pemasukanPerBulan[$index] = (int) $row['total'];
    } else {
        $pengeluaranPerBulan[$index] = (int) $row['total'];
    }
}

$riwayat = mysqli_query($conn, "
    SELECT tanggal, jenis, jumlah, keterangan
    FROM transaksi_keuangan
    ORDER BY tanggal DESC, id_transaksi DESC
    LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #eaf3ff, #f8fbff); }
        .sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #0f2f59, #123d75); position: fixed; left: -250px; top: 0; transition: 0.3s; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar.active { left: 0; }
        .sidebar h2 { color: white; text-align: center; padding: 20px; margin: 0; }
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #dbe9ff; text-decoration: none; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); }
        .sidebar a.active { background: rgba(255,255,255,0.14); color: #ffffff; margin: 6px 12px; border-radius: 14px; }
        .menu-bawah a { background: #0d2c54; }
        .main { margin-left: 0; padding: 35px; transition: 0.3s; }
        .main.shift { margin-left: 250px; }
        .header, .card { background: white; border-radius: 18px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .header { padding: 20px; display: flex; align-items: center; gap: 14px; }
        .menu-icon { cursor: pointer; padding: 10px; border-radius: 10px; }
        .menu-icon:hover { background: rgba(47,128,237,0.1); }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; margin-top: 24px; }
        .card { padding: 24px; }
        .metric { font-size: 28px; font-weight: 800; color: #0f3c74; margin-top: 12px; }
        form { display: grid; gap: 12px; }
        input, textarea { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #ccd7e5; font-family: inherit; box-sizing: border-box; }
        textarea { min-height: 100px; resize: vertical; }
        button { padding: 12px 18px; border: none; border-radius: 10px; background: linear-gradient(90deg, #4da6ff, #2f80ed); color: white; font-weight: 700; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #eef2f7; }
        th { background: #f3f7fd; color: #103a70; }
        .overlay { position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; top: 0; left: 0; }
        .overlay.active { display: block; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } .sidebar { width: 70%; left: -70%; } .main.shift { margin-left: 0; } }
    </style>
</head>
<body>
<?php renderAdminSidebar('laporan.php'); ?>
<div class="main" id="main">
    <div class="header">
        <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
        <div>
            <h2 style="margin:0;">Laporan Keuangan</h2>
            <p style="margin:4px 0 0; color:#637892;">Pemasukan dari pembayaran dan pengeluaran operasional homestay.</p>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h3>Total Pemasukan</h3>
            <div class="metric">Rp <?= number_format((int) ($summary['total_pemasukan'] ?? 0)); ?></div>
        </div>
        <div class="card">
            <h3>Total Pengeluaran</h3>
            <div class="metric">Rp <?= number_format((int) ($summary['total_pengeluaran'] ?? 0)); ?></div>
        </div>
        <div class="card">
            <h3>Saldo</h3>
            <div class="metric">Rp <?= number_format((int) (($summary['total_pemasukan'] ?? 0) - ($summary['total_pengeluaran'] ?? 0))); ?></div>
        </div>
    </div>

    <div class="grid">
        <div class="card" style="grid-column: span 2;">
            <h3>Grafik Pemasukan & Pengeluaran</h3>
            <canvas id="financeChart"></canvas>
        </div>
        <div class="card">
            <h3>Input Pengeluaran</h3>
            <form method="POST">
                <input type="date" name="tanggal" required value="<?= date('Y-m-d'); ?>">
                <input type="number" name="jumlah" placeholder="Jumlah pengeluaran" required min="0">
                <textarea name="keterangan" placeholder="Keterangan pengeluaran" required></textarea>
                <button type="submit" name="tambah_pengeluaran">Simpan Pengeluaran</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:24px;">
        <h3>Riwayat Transaksi</h3>
        <table>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
            </tr>
            <?php $no = 1; while ($row = mysqli_fetch_assoc($riwayat)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['tanggal'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($row['jenis']); ?></td>
                    <td>Rp <?= number_format((int) $row['jumlah']); ?></td>
                    <td><?= htmlspecialchars($row['keterangan'] ?: '-'); ?></td>
                </tr>
            <?php endwhile; ?>
            <?php if ($no === 1): ?>
                <tr><td colspan="5">Belum ada transaksi keuangan.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<script>
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const main = document.getElementById("main");
function syncSidebarLayout(){ if (window.innerWidth > 900 && sidebar.classList.contains("active")) { main.classList.add("shift"); overlay.classList.remove("active"); return; } main.classList.remove("shift"); }
function toggleSidebar(){ sidebar.classList.toggle("active"); if (window.innerWidth <= 900) { overlay.classList.toggle("active"); } else { overlay.classList.remove("active"); } syncSidebarLayout(); }
function closeSidebar(){ sidebar.classList.remove("active"); overlay.classList.remove("active"); syncSidebarLayout(); }
new Chart(document.getElementById('financeChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_values($bulan)); ?>,
        datasets: [
            {
                label: 'Pemasukan',
                data: <?= json_encode(array_values($pemasukanPerBulan)); ?>,
                borderColor: '#2f80ed',
                backgroundColor: 'rgba(47, 128, 237, 0.15)',
                tension: 0.35,
                fill: true
            },
            {
                label: 'Pengeluaran',
                data: <?= json_encode(array_values($pengeluaranPerBulan)); ?>,
                borderColor: '#f2994a',
                backgroundColor: 'rgba(242, 153, 74, 0.12)',
                tension: 0.35,
                fill: true
            }
        ]
    },
    options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true } } }
});
window.addEventListener("resize", syncSidebarLayout); syncSidebarLayout();
</script>
</body>
</html>
