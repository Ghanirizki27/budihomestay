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

/* ===============================
   PROSES KIRIM KELUHAN
=================================*/
if (isset($_POST['kirim_keluhan']) && !empty($penyewa['id_penyewa'])) {
    $isiKeluhan = mysqli_real_escape_string($conn, trim($_POST['isi_keluhan']));
    if ($isiKeluhan !== '') {
        mysqli_query($conn, "
            INSERT INTO laporan_keluhan (id_penyewa, isi_keluhan, tanggal, status)
            VALUES ('".(int) $penyewa['id_penyewa']."', '$isiKeluhan', CURDATE(), 'Diajukan')
        ");
        header("Location: penyewa_keluhan.php?status=success");
        exit;
    }
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
    <title>Laporan Keluhan - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #eaf3ff, #f8fbff); color: #13355f; }
        
        /* SIDEBAR */
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
        
        /* HEADER KONSISTEN */
        .header { background: linear-gradient(120deg, #1f4f8f, #2f80ed); color: white; border-radius: 24px; padding: 28px; display: flex; align-items: center; justify-content: space-between; gap: 20px; box-shadow: 0 18px 35px rgba(47,128,237,0.25); }
        .menu-icon { cursor: pointer; padding: 10px; border-radius: 10px; font-size: 20px; background: rgba(255,255,255,0.15); display: flex; align-items: center; }

        /* CARDS */
        .card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); margin-top: 24px; border: none; }
        .card h3 { margin-top: 0; color: #0f3c74; font-size: 18px; border-bottom: 2px solid #f0f4f8; padding-bottom: 12px; margin-bottom: 20px; }
        
        /* FORM */
        textarea { width: 100%; min-height: 120px; padding: 15px; border-radius: 15px; border: 1px solid #ccd7e5; font-family: inherit; resize: vertical; box-sizing: border-box; transition: 0.3s; }
        textarea:focus { border-color: #2f80ed; outline: none; box-shadow: 0 0 0 4px rgba(47,128,237,0.1); }
        .btn-kirim { margin-top: 15px; padding: 12px 25px; border: none; border-radius: 12px; background: linear-gradient(90deg, #4da6ff, #2f80ed); color: white; font-weight: 700; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-kirim:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(47,128,237,0.2); }

        /* COMPLAINT LIST */
        .complaint-item { padding: 20px; border-radius: 18px; background: #fdfdfd; border: 1px solid #edf2f7; margin-bottom: 20px; }
        .complaint-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .badge { padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .badge-diajukan { background: #edf2fa; color: #5f7187; }
        .badge-diproses { background: #fff4db; color: #a86c00; }
        .badge-selesai { background: #e8f8ee; color: #1c854a; }
        
        .complaint-text { line-height: 1.6; color: #444; margin: 10px 0; }
        .meta { font-size: 13px; color: #8898aa; }

        /* ADMIN FEEDBACK */
        .admin-reply { margin-top: 15px; padding: 15px; border-radius: 15px; background: #f0f7ff; border-left: 4px solid #2f80ed; position: relative; }
        .admin-reply::before { content: "\f0e5"; font-family: "Font Awesome 6 Free"; font-weight: 900; position: absolute; right: 15px; top: 12px; opacity: 0.1; font-size: 20px; }
        .reply-label { display: block; font-size: 12px; font-weight: 800; color: #2f80ed; text-transform: uppercase; margin-bottom: 5px; }

        .alert-success { padding: 15px 20px; border-radius: 12px; background: #e8f8ee; color: #1c854a; font-weight: 600; border-left: 5px solid #27ae60; margin-top: 24px; }

        @media (max-width: 900px) { .sidebar { width: 70%; left: -70%; } .main.shift { margin-left: 0; } .header { flex-direction: column; align-items: flex-start; } .header-info-right { text-align: left !important; margin-top: 15px; } }
    </style>
</head>
<body>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderTenantSidebar('penyewa_keluhan.php'); ?>

    <div class="main" id="main">
        <!-- HEADER KONSISTEN -->
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1 style="font-size: 24px; margin:0;">Laporan Keluhan</h1>
                    <p style="margin:5px 0 0; opacity:0.8; font-size: 14px;">Halo, <?= htmlspecialchars($_SESSION['nama']); ?>! Sampaikan kendala fasilitas Anda di sini.</p>
                </div>
            </div>
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600;">Akun Penyewa</div>
            </div>
        </div>

        <?php if(isset($_GET['status']) && $_GET['status'] == "success"): ?>
            <div class="alert-success"><i class="fa-solid fa-circle-check"></i> Keluhan Anda berhasil dikirim ke admin!</div>
        <?php endif; ?>

        <!-- FORM KIRIM KELUHAN -->
        <div class="card">
            <h3><i class="fa-solid fa-bullhorn" style="color: #2f80ed;"></i> Kirim Keluhan Baru</h3>
            <form method="POST">
                <textarea name="isi_keluhan" placeholder="Ceritakan detail kendala Anda (misal: Keran air bocor, WiFi lambat, dll)..." required></textarea>
                <button type="submit" name="kirim_keluhan" class="btn-kirim">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Laporan Sekarang
                </button>
            </form>
        </div>

        <!-- RIWAYAT KELUHAN -->
        <div class="card">
            <h3><i class="fa-solid fa-history"></i> Riwayat Keluhan Anda</h3>
            <?php 
            $adaKeluhan = false; 
            while ($row = mysqli_fetch_assoc($keluhan)): 
                $adaKeluhan = true;
                $status_class = 'badge-' . strtolower($row['status']);
            ?>
                <div class="complaint-item">
                    <div class="complaint-header">
                        <span class="meta"><i class="fa-solid fa-calendar-alt"></i> <?= date('d M Y', strtotime($row['tanggal'])); ?></span>
                        <span class="badge <?= $status_class; ?>"><?= htmlspecialchars($row['status']); ?></span>
                    </div>
                    <div class="complaint-text">
                        "<?= nl2br(htmlspecialchars($row['isi_keluhan'])); ?>"
                    </div>
                    
                    <?php if (!empty($row['tanggapan_admin'])): ?>
                        <div class="admin-reply">
                            <span class="reply-label">Tanggapan Pengelola:</span>
                            <div style="font-size: 14px; color: #244b78;"><?= nl2br(htmlspecialchars($row['tanggapan_admin'])); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="meta" style="margin-top: 10px; font-style: italic;">
                            <i class="fa-solid fa-clock"></i> Menunggu tanggapan admin...
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>

            <?php if (!$adaKeluhan): ?>
                <div style="text-align: center; padding: 30px; color: #999;">
                    <i class="fa-solid fa-face-smile" style="font-size: 40px; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                    Belum ada laporan keluhan yang dikirim.
                </div>
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

    window.addEventListener("resize", syncSidebarLayout);
    syncSidebarLayout();
    </script>
</body>
</html>