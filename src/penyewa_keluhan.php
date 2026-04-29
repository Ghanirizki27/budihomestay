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

if (isset($_POST['kirim_keluhan']) && !empty($penyewa['id_penyewa'])) {
    $isiKeluhan = mysqli_real_escape_string($conn, trim($_POST['isi_keluhan']));
    if ($isiKeluhan !== '') {
        mysqli_query($conn, "
            INSERT INTO laporan_keluhan (id_penyewa, isi_keluhan, tanggal, status)
            VALUES ('".(int) $penyewa['id_penyewa']."', '$isiKeluhan', CURDATE(), 'Diajukan')
        ");
    }
    header("Location: penyewa_keluhan.php");
    exit;
}

$keluhan = mysqli_query($conn, "
    SELECT isi_keluhan, tanggal, status, tanggapan_admin
    FROM laporan_keluhan
    WHERE id_penyewa = '".(int) ($penyewa['id_penyewa'] ?? 0)."'
    ORDER BY id_keluhan DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keluhan</title>
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
        .card { margin-top: 24px; padding: 24px; }
        textarea { width: 100%; min-height: 120px; padding: 14px; border-radius: 14px; border: 1px solid #ccd7e5; font-family: inherit; resize: vertical; }
        button { margin-top: 16px; padding: 12px 18px; border: none; border-radius: 10px; background: linear-gradient(90deg, #4da6ff, #2f80ed); color: white; font-weight: 700; cursor: pointer; }
        .item + .item { margin-top: 14px; padding-top: 14px; border-top: 1px solid #edf1f7; }
        .meta { color: #6b7f97; font-size: 14px; }
        .comment-box { margin-top: 12px; padding: 14px; border-radius: 14px; background: #f3f8ff; color: #244b78; }
        .overlay { position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; top: 0; left: 0; z-index: 999; }
        .overlay.active { display: block; }
        @media (max-width: 900px) { .sidebar { width: 70%; left: -70%; } .main.shift { margin-left: 0; } }
    </style>
</head>
<body>
<?php renderTenantSidebar('penyewa_keluhan.php'); ?>
<div class="main" id="main">
    <div class="header">
        <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
        <div>
            <h2>Laporan Keluhan</h2>
            <p>Sampaikan keluhan ke admin dan pantau status penanganannya.</p>
        </div>
    </div>

    <div class="card">
        <h3>Kirim Keluhan Baru</h3>
        <form method="POST">
            <textarea name="isi_keluhan" placeholder="Tuliskan keluhan Anda di sini..." required></textarea>
            <button type="submit" name="kirim_keluhan">Kirim Keluhan</button>
        </form>
    </div>

    <div class="card">
        <h3>Riwayat Keluhan</h3>
        <?php $adaKeluhan = false; while ($row = mysqli_fetch_assoc($keluhan)): $adaKeluhan = true; ?>
            <div class="item">
                <strong><?= htmlspecialchars($row['status']); ?></strong>
                <p><?= nl2br(htmlspecialchars($row['isi_keluhan'])); ?></p>
                <div class="meta">Tanggal: <?= htmlspecialchars($row['tanggal']); ?></div>
                <div class="comment-box">
                    <strong>Komentar Admin</strong>
                    <div style="margin-top:6px;"><?= nl2br(htmlspecialchars($row['tanggapan_admin'] ?: 'Belum ada tanggapan dari admin.')); ?></div>
                </div>
            </div>
        <?php endwhile; ?>
        <?php if (!$adaKeluhan): ?>
            <p>Belum ada keluhan yang dikirim.</p>
        <?php endif; ?>
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
