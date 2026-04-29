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
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #eaf3ff, #f8fbff);
            color: #13355f;
        }

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
            z-index: 1000;
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
        }

        .menu-bawah a {
            background: #0d2c54;
        }

        .main {
            margin-left: 0;
            padding: 35px;
            transition: 0.3s;
        }

        .main.shift {
            margin-left: 250px;
        }

        .header {
            background: linear-gradient(120deg, #1f4f8f, #2f80ed);
            color: white;
            border-radius: 24px;
            padding: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 18px 35px rgba(47,128,237,0.25);
        }

        .header h1,
        .header p {
            margin: 0;
        }

        .header p {
            margin-top: 10px;
            opacity: 0.9;
        }

        .menu-icon {
            cursor: pointer;
            padding: 10px;
            border-radius: 10px;
            font-size: 20px;
            background: rgba(255,255,255,0.15);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
            margin-top: 24px;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 12px 28px rgba(18,61,117,0.08);
        }

        .card h3,
        .card p {
            margin: 0;
        }

        .card p {
            margin-top: 10px;
            color: #5c6f87;
            line-height: 1.6;
        }

        .metric {
            font-size: 30px;
            font-weight: 800;
            color: #0f3c74;
            margin-top: 12px;
        }

        .status-chip {
            display: inline-flex;
            margin-top: 14px;
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 14px;
        }

        .status-ok {
            background: #e8f8ee;
            color: #1c854a;
        }

        .status-due {
            background: #fff4db;
            color: #a86c00;
        }

        .status-late {
            background: #ffe7e7;
            color: #c23d3d;
        }

        .status-wait {
            background: #edf2fa;
            color: #5f7187;
        }

        .announcements {
            margin-top: 24px;
        }

        .announcement-item + .announcement-item {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #edf1f7;
        }

        .announcement-item small,
        .empty-note {
            color: #6e7f95;
        }

        .overlay {
            position: fixed;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            display: none;
            top: 0;
            left: 0;
            z-index: 999;
        }

        .overlay.active {
            display: block;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

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
    <?php renderTenantSidebar('home_penyewa.php'); ?>

    <div class="main" id="main">
        <div class="header">
            <div style="display:flex; align-items:flex-start; gap:14px;">
                <div class="menu-icon" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </div>
                <div>
                    <h1>Halo, <?= htmlspecialchars($_SESSION['nama']); ?>!</h1>
                    <p>Data kamar, harga sewa, tanggal masuk, dan pembayaran Anda akan selalu mengikuti update dari admin.</p>
                </div>
            </div>
            <div>
                <strong><?= htmlspecialchars($penyewa['akun_username'] ?? '-'); ?></strong><br>
                <small>Akun penyewa</small>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Kamar</h3>
                <div class="metric"><?= htmlspecialchars($penyewa['nomor_kamar'] ?? '-'); ?></div>
                <p>Nomor kamar aktif Anda saat ini.</p>
            </div>

            <div class="card">
                <h3>Biaya Sewa</h3>
                <div class="metric">Rp <?= number_format((int) ($penyewa['harga'] ?? 0)); ?></div>
                <p>Nominal sewa bulanan berdasarkan kamar yang ditempati.</p>
            </div>

            <div class="card">
                <h3>Tanggal Masuk</h3>
                <div class="metric"><?= htmlspecialchars($penyewa['tanggal_masuk'] ?? '-'); ?></div>
                <p>Tanggal mulai sewa yang tercatat oleh admin.</p>
            </div>

            <div class="card">
                <h3>Status Tagihan</h3>
                <div class="metric"><?= htmlspecialchars($summary['jatuh_tempo'] ?? '-'); ?></div>
                <p>Jatuh tempo pembayaran berikutnya.</p>
                <span class="status-chip <?= htmlspecialchars($summary['status_class']); ?>">
                    <?= htmlspecialchars($summary['status_label']); ?>
                </span>
            </div>
        </div>

        <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
            <div class="card">
                <h3>Pembayaran Terakhir</h3>
                <div class="metric"><?= htmlspecialchars($summary['pembayaran_terakhir'] ?? '-'); ?></div>
                <p>Tanggal pembayaran terakhir yang sudah divalidasi admin.</p>
            </div>

            <div class="card">
                <h3>Status Sewa</h3>
                <div class="metric"><?= htmlspecialchars($penyewa['status_sewa'] ?? '-'); ?></div>
                <p>Status sewa aktif dari data admin.</p>
            </div>
        </div>

        <div class="card announcements">
            <h3>Pengumuman Terbaru</h3>
            <?php if (mysqli_num_rows($pengumuman) > 0): ?>
                <?php while ($item = mysqli_fetch_assoc($pengumuman)): ?>
                    <div class="announcement-item">
                        <strong><?= htmlspecialchars($item['judul']); ?></strong>
                        <p><?= nl2br(htmlspecialchars($item['isi'])); ?></p>
                        <small><?= htmlspecialchars($item['tanggal_dibuat']); ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty-note">Belum ada pengumuman terbaru.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const main = document.getElementById("main");

    function syncSidebarLayout() {
        if (window.innerWidth > 900 && sidebar.classList.contains("active")) {
            main.classList.add("shift");
            overlay.classList.remove("active");
            return;
        }

        main.classList.remove("shift");
    }

    function toggleSidebar() {
        sidebar.classList.toggle("active");

        if (window.innerWidth <= 900) {
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

    window.addEventListener("resize", syncSidebarLayout);
    syncSidebarLayout();
    </script>
</body>
</html>
