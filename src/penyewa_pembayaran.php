<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "tenant_nav.php";
include_once "tenant_context.php";

requireRole('penyewa');
ensureAppSchema($conn);

$penyewa = getLoggedInTenant($conn);
$summary = getTenantPaymentSummary($conn, (int) ($penyewa['id_penyewa'] ?? 0), $penyewa['tanggal_masuk'] ?? null);
$riwayat = mysqli_query($conn, "
    SELECT tanggal_bayar, jatuh_tempo, jumlah, status
    FROM pembayaran
    WHERE id_penyewa = '".(int) ($penyewa['id_penyewa'] ?? 0)."'
    ORDER BY tanggal_bayar DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Penyewa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #eaf3ff, #f8fbff); }
        .sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #0f2f59, #123d75); position: fixed; left: -250px; top: 0; transition: 0.3s; display: flex; flex-direction: column; justify-content: space-between; z-index: 1000; }
        .sidebar.active { left: 0; }
        .sidebar h2 { color: white; text-align: center; padding: 20px; margin: 0; }
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #dbe9ff; text-decoration: none; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); }
        .sidebar a.active { background: rgba(255,255,255,0.14); color: #ffffff; margin: 6px 12px; border-radius: 14px; }
        .menu-bawah a { background: #0d2c54; }
        .main { margin-left: 0; padding: 35px; transition: 0.3s; }
        .main.shift { margin-left: 250px; }
        .header, .card { background: white; border-radius: 20px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); }
        .header { padding: 24px; display: flex; align-items: center; gap: 14px; }
        .menu-icon { cursor: pointer; padding: 10px; border-radius: 10px; background: #eef5ff; color: #2f80ed; }
        .header h2, .header p { margin: 0; }
        .header p { margin-top: 6px; color: #637892; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; margin-top: 24px; }
        .card { padding: 24px; }
        .metric { font-size: 28px; font-weight: 800; color: #0f3c74; margin-top: 12px; }
        .status-chip { display: inline-flex; margin-top: 14px; padding: 8px 14px; border-radius: 999px; font-weight: 700; font-size: 14px; }
        .status-ok { background: #e8f8ee; color: #1c854a; }
        .status-due { background: #fff4db; color: #a86c00; }
        .status-late { background: #ffe7e7; color: #c23d3d; }
        .status-wait { background: #edf2fa; color: #5f7187; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #eef2f7; }
        th { background: #f3f7fd; color: #103a70; }
        .overlay { position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; top: 0; left: 0; z-index: 999; }
        .overlay.active { display: block; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } .sidebar { width: 70%; left: -70%; } .main.shift { margin-left: 0; } }
    </style>
</head>
<body>
<?php renderTenantSidebar('penyewa_pembayaran.php'); ?>
<div class="main" id="main">
    <div class="header">
        <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
        <div>
            <h2>Pembayaran</h2>
            <p>Riwayat pembayaran dan rincian tagihan dari data admin.</p>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h3>Kamar</h3>
            <div class="metric"><?= htmlspecialchars($penyewa['nomor_kamar'] ?? '-'); ?></div>
        </div>
        <div class="card">
            <h3>Harga Sewa</h3>
            <div class="metric">Rp <?= number_format((int) ($penyewa['harga'] ?? 0)); ?></div>
        </div>
        <div class="card">
            <h3>Jatuh Tempo</h3>
            <div class="metric"><?= htmlspecialchars($summary['jatuh_tempo'] ?? '-'); ?></div>
            <span class="status-chip <?= htmlspecialchars($summary['status_class']); ?>"><?= htmlspecialchars($summary['status_label']); ?></span>
        </div>
    </div>

    <div class="card" style="margin-top:24px;">
        <h3>Riwayat Pembayaran</h3>
        <table>
            <tr>
                <th>No</th>
                <th>Tanggal Bayar</th>
                <th>Jatuh Tempo</th>
                <th>Jumlah</th>
                <th>Status</th>
            </tr>
            <?php $no = 1; while ($row = mysqli_fetch_assoc($riwayat)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['tanggal_bayar'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($row['jatuh_tempo'] ?: '-'); ?></td>
                    <td>Rp <?= number_format((int) $row['jumlah']); ?></td>
                    <td><?= htmlspecialchars($row['status']); ?></td>
                </tr>
            <?php endwhile; ?>
            <?php if ($no === 1): ?>
                <tr><td colspan="5">Belum ada riwayat pembayaran.</td></tr>
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
window.addEventListener("resize", syncSidebarLayout); syncSidebarLayout();
</script>
</body>
</html>
