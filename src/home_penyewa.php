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
$id_penyewa = (int) ($penyewa['id_penyewa'] ?? 0);

// Ambil summary dasar (jatuh tempo, dsb)
$summary = getTenantPaymentSummary($conn, $id_penyewa, $penyewa['tanggal_masuk'] ?? null);

// --- PERBAIKAN: Ambil Tanggal Pembayaran Terakhir Langsung dari Database ---
$q_last_pay = mysqli_query($conn, "
    SELECT tanggal_bayar 
    FROM pembayaran 
    WHERE id_penyewa = '$id_penyewa' AND status = 'Lunas' 
    ORDER BY tanggal_bayar DESC 
    LIMIT 1
");
$data_last_pay = mysqli_fetch_assoc($q_last_pay);
$pembayaran_terakhir = $data_last_pay ? date('d M Y', strtotime($data_last_pay['tanggal_bayar'])) : '-';

$pengumuman = mysqli_query($conn, "
    SELECT judul, isi, tanggal_dibuat
    FROM pengumuman
    ORDER BY tanggal_dibuat DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penyewa - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #eaf3ff, #f8fbff); color: #13355f; }
        .sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #0f2f59, #123d75); position: fixed; left: -250px; top: 0; transition: 0.3s; z-index: 1000; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar.active { left: 0; }
        .sidebar h2 { color: white; text-align: center; padding: 20px; margin: 0; font-size: 22px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #dbe9ff; text-decoration: none; }
        .sidebar a.active { background: rgba(255,255,255,0.14); color: #ffffff; margin: 6px 12px; border-radius: 14px; }
        .menu-bawah a { background: #081b33; } 
        .overlay { position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; top: 0; left: 0; z-index: 999; }
        .overlay.active { display: block; }
        .main { margin-left: 0; padding: 35px; transition: 0.3s; }
        .main.shift { margin-left: 250px; }
        .header { background: linear-gradient(120deg, #1f4f8f, #2f80ed); color: white; border-radius: 24px; padding: 28px; display: flex; align-items: center; justify-content: space-between; gap: 20px; box-shadow: 0 18px 35px rgba(47,128,237,0.25); }
        .header h1, .header p { margin: 0; }
        .header p { margin-top: 8px; opacity: 0.9; font-size: 14px; }
        .menu-icon { cursor: pointer; padding: 10px; border-radius: 10px; font-size: 20px; background: rgba(255,255,255,0.15); display: flex; align-items: center; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 22px; margin-top: 24px; }
        .card { background: white; border-radius: 20px; padding: 24px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); border: none; }
        .card h3 { margin: 0; color: #5c6f87; font-size: 15px; text-transform: uppercase; letter-spacing: 0.5px; }
        .card p { margin-top: 10px; color: #6e7f95; font-size: 13px; line-height: 1.5; }
        .metric { font-size: 28px; font-weight: 800; color: #0f3c74; margin-top: 12px; }
        .status-chip { display: inline-flex; margin-top: 14px; padding: 8px 14px; border-radius: 999px; font-weight: 700; font-size: 12px; text-transform: uppercase; }
        .status-ok { background: #e8f8ee; color: #1c854a; }
        .status-due { background: #fff4db; color: #a86c00; }
        .status-late { background: #ffe7e7; color: #c23d3d; }
        .status-wait { background: #edf2fa; color: #5f7187; }
        .announcements { margin-top: 24px; }
        .announcement-item + .announcement-item { margin-top: 16px; padding-top: 16px; border-top: 1px solid #edf1f7; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } .sidebar { width: 70%; left: -70%; } .main.shift { margin-left: 0; } .header { flex-direction: column; align-items: flex-start; } .header-info-right { text-align: left !important; margin-top: 15px; } }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderTenantSidebar('home_penyewa.php'); ?>

    <div class="main" id="main">
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1>Halo, <?= htmlspecialchars($_SESSION['nama']); ?>!</h1>
                    <p>Pantau data kamar, masa sewa, dan riwayat pembayaran Anda secara real-time.</p>
                </div>
            </div>
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600;">Akun: <strong><?= htmlspecialchars($penyewa['akun_username'] ?? '-'); ?></strong></div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Kamar Aktif</h3>
                <div class="metric"><?= htmlspecialchars($penyewa['nomor_kamar'] ?? '-'); ?></div>
                <p>Nomor unit kamar yang Anda tempati.</p>
            </div>
            <div class="card">
                <h3>Biaya Sewa</h3>
                <div class="metric">Rp <?= number_format((int) ($penyewa['harga'] ?? 0)); ?></div>
                <p>Besaran biaya sewa bulanan.</p>
            </div>
            <div class="card">
                <h3>Tanggal Masuk</h3>
                <div class="metric"><?= htmlspecialchars($penyewa['tanggal_masuk'] ?? '-'); ?></div>
                <p>Mulai tercatat sebagai penghuni.</p>
            </div>
            <div class="card">
                <h3>Status Tagihan</h3>
                <div class="metric"><?= htmlspecialchars($summary['jatuh_tempo'] ?? '-'); ?></div>
                <span class="status-chip <?= htmlspecialchars($summary['status_class']); ?>">
                    <?= htmlspecialchars($summary['status_label']); ?>
                </span>
            </div>
        </div>

        <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
            <div class="card">
                <h3>Pembayaran Terakhir</h3>
                <!-- MENGGUNAKAN VARIABEL YANG BARU KITA BUAT DI ATAS -->
                <div class="metric"><?= $pembayaran_terakhir; ?></div>
                <p>Tanggal pembayaran terakhir yang telah divalidasi oleh admin.</p>
            </div>
            <div class="card">
                <h3>Status Sewa</h3>
                <div class="metric"><?= htmlspecialchars($penyewa['status_sewa'] ?? '-'); ?></div>
                <p>Keterangan status huni aktif.</p>
            </div>
        </div>

        <div class="card announcements">
            <h3><i class="fa-solid fa-bullhorn" style="color: #2f80ed; margin-right: 10px;"></i> Pengumuman Terbaru</h3>
            <?php if (mysqli_num_rows($pengumuman) > 0): ?>
                <?php while ($item = mysqli_fetch_assoc($pengumuman)): ?>
                    <div class="announcement-item">
                        <strong><?= htmlspecialchars($item['judul']); ?></strong>
                        <p><?= nl2br(htmlspecialchars($item['isi'])); ?></p>
                        <small><i class="fa-solid fa-clock"></i> <?= date('d M Y', strtotime($item['tanggal_dibuat'])); ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">Belum ada pengumuman terbaru.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const main = document.getElementById("main");

    function syncSidebarLayout() { if (window.innerWidth > 900 && sidebar.classList.contains("active")) { main.classList.add("shift"); return; } main.classList.remove("shift"); }
    function toggleSidebar() { sidebar.classList.toggle("active"); if (window.innerWidth <= 900) { overlay.classList.toggle("active"); } syncSidebarLayout(); }
    function closeSidebar() { sidebar.classList.remove("active"); overlay.classList.remove("active"); syncSidebarLayout(); }

    function updateDateTime() {
        const skrg = new Date();
        document.getElementById("tanggal").textContent = skrg.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        document.getElementById("waktu").textContent = skrg.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setInterval(updateDateTime, 1000); updateDateTime();
    window.addEventListener("resize", syncSidebarLayout); syncSidebarLayout();
    </script>
</body>
</html>