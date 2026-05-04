<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "admin_nav.php";

requireRole('admin');
ensureAppSchema($conn);

/* ===============================
   PROSES UPDATE & HAPUS
=================================*/
if (isset($_POST['update_status'])) {
    $idKeluhan = (int) $_POST['id_keluhan'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $tanggapan = mysqli_real_escape_string($conn, trim($_POST['tanggapan_admin']));
    mysqli_query($conn, "UPDATE laporan_keluhan SET status='$status', tanggapan_admin='$tanggapan' WHERE id_keluhan='$idKeluhan'");
    header("Location: keluhan_admin.php?pesan=update");
    exit;
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM laporan_keluhan WHERE id_keluhan='$id'");
    header("Location: keluhan_admin.php?pesan=hapus");
    exit;
}

// Ambil Keluhan Aktif (Belum Selesai)
$keluhan_aktif = mysqli_query($conn, "
    SELECT l.*, p.nama, k.nomor_kamar 
    FROM laporan_keluhan l
    LEFT JOIN penyewa p ON l.id_penyewa = p.id_penyewa
    LEFT JOIN kamar k ON p.id_kamar = k.id_kamar
    WHERE l.status != 'Selesai'
    ORDER BY l.tanggal DESC
");

// Ambil Riwayat (Sudah Selesai)
$riwayat_keluhan = mysqli_query($conn, "
    SELECT l.*, p.nama, k.nomor_kamar 
    FROM laporan_keluhan l
    LEFT JOIN penyewa p ON l.id_penyewa = p.id_penyewa
    LEFT JOIN kamar k ON p.id_kamar = k.id_kamar
    WHERE l.status = 'Selesai'
    ORDER BY l.id_keluhan DESC LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Keluhan - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f8fbff; color: #13355f; }
        
        /* SIDEBAR (Diperbaiki) */
        .sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #0f2f59, #123d75); position: fixed; left: -250px; top: 0; transition: 0.3s; z-index: 1000; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar.active { left: 0; }
        .sidebar h2 { color: white; text-align: center; padding: 20px; margin: 0; font-size: 22px; } /* Ini yang hilang sebelumnya */
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #dbe9ff; text-decoration: none; }
        .sidebar a.active { background: rgba(255,255,255,0.14); color: #ffffff; margin: 6px 12px; border-radius: 14px; }
        .menu-bawah a { background: #081b33; } 
        
        .overlay { position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; top: 0; left: 0; z-index: 999; }
        .overlay.active { display: block; }
        .main { margin-left: 0; padding: 35px; transition: 0.3s; }
        .main.shift { margin-left: 250px; }

        /* HEADER */
        .header { background: linear-gradient(120deg, #1f4f8f, #2f80ed); color: white; border-radius: 24px; padding: 28px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 18px 35px rgba(47,128,237,0.25); }
        .menu-icon { cursor: pointer; padding: 10px; border-radius: 10px; background: rgba(255,255,255,0.15); display: flex; }

        /* COMPLAINT CARD STYLE */
        .section-title { margin: 30px 0 15px; display: flex; align-items: center; gap: 10px; color: #0f3c74; }
        .complaint-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; gap: 20px; border-left: 6px solid #2f80ed; }
        .user-info { min-width: 180px; border-right: 1px solid #eee; padding-right: 20px; }
        .complaint-content { flex: 1; }
        .complaint-text { background: #f0f7ff; padding: 15px; border-radius: 15px; font-style: italic; margin-bottom: 15px; position: relative; }
        
        /* FORM */
        .action-box { background: #fdfdfd; padding: 20px; border-radius: 15px; border: 1px solid #eee; }
        textarea { width: 100%; border: 1px solid #ccd7e5; border-radius: 10px; padding: 12px; font-family: inherit; resize: vertical; box-sizing: border-box; }
        .btn-update { background: #2f80ed; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        
        /* STATUS */
        .badge { padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .badge-diajukan { background: #edf2fa; color: #5f7187; }
        .badge-diproses { background: #fff4db; color: #a86c00; }
        
        /* HISTORY TABLE */
        .history-card { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.03); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; color: #5c6f87; font-size: 12px; background: #f8fbff; text-transform: uppercase;}
        td { padding: 12px; border-bottom: 1px solid #f9f9f9; font-size: 14px; }

        @media (max-width: 900px) { .complaint-card { flex-direction: column; } .user-info { border-right: none; border-bottom: 1px solid #eee; padding-bottom: 15px; min-width: auto; } .main.shift { margin-left: 0; } .header {flex-direction: column; align-items: flex-start;} .header-info-right { text-align: left !important; margin-top: 15px;} }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderAdminSidebar('keluhan_admin.php'); ?>

    <div class="main" id="main">
        <!-- HEADER -->
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1 style="font-size: 24px; margin:0;">Manajemen Keluhan</h1>
                    <p style="margin:5px 0 0; opacity:0.8; font-size: 14px;">Respon cepat keluhan penghuni untuk kenyamanan bersama.</p>
                </div>
            </div>
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600;">Level: Administrator</div>
            </div>
        </div>

        <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'update'): ?>
            <div style="padding: 15px 20px; border-radius: 12px; background: #d4edda; color: #155724; font-weight: 600; border-left: 5px solid #28a745; margin-top: 24px;">
                <i class="fa-solid fa-circle-check"></i> Tanggapan berhasil disimpan!
            </div>
        <?php endif; ?>

        <!-- BAGIAN 1: KELUHAN AKTIF (CARD MODE) -->
        <h3 class="section-title"><i class="fa-solid fa-spinner fa-spin"></i> Keluhan Perlu Tindakan</h3>
        
        <?php if (mysqli_num_rows($keluhan_aktif) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($keluhan_aktif)): ?>
            <div class="complaint-card" style="border-left-color: <?= $row['status'] == 'Diproses' ? '#f1c40f' : '#2f80ed'; ?>;">
                <div class="user-info">
                    <strong style="font-size:18px;"><?= htmlspecialchars($row['nama']); ?></strong><br>
                    <span style="color:#2f80ed; font-weight:bold;">Kamar <?= htmlspecialchars($row['nomor_kamar']); ?></span><br>
                    <small style="color:#888;"><?= date('d M Y, H:i', strtotime($row['tanggal'])); ?></small><br><br>
                    <span class="badge badge-<?= strtolower($row['status']); ?>"><?= $row['status']; ?></span>
                </div>
                
                <div class="complaint-content">
                    <div class="complaint-text">
                        <i class="fa-solid fa-quote-left" style="opacity:0.2; position:absolute; top:10px; left:10px; font-size:25px;"></i>
                        <p style="margin:0; padding-left:20px;"><?= nl2br(htmlspecialchars($row['isi_keluhan'])); ?></p>
                    </div>
                    
                    <div class="action-box">
                        <form method="POST">
                            <input type="hidden" name="id_keluhan" value="<?= $row['id_keluhan']; ?>">
                            <label style="font-weight:bold; font-size:13px; display:block; margin-bottom:8px;">Tanggapan & Solusi Admin:</label>
                            <textarea name="tanggapan_admin" placeholder="Tulis rencana perbaikan atau jawaban untuk penyewa..."><?= htmlspecialchars($row['tanggapan_admin']); ?></textarea>
                            
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px;">
                                <select name="status" style="padding:8px; border-radius:8px; border:1px solid #ddd; font-family: inherit;">
                                    <option value="Diajukan" <?= $row['status'] == 'Diajukan' ? 'selected' : ''; ?>>Tandai: Diajukan</option>
                                    <option value="Diproses" <?= $row['status'] == 'Diproses' ? 'selected' : ''; ?>>Tandai: Diproses</option>
                                    <option value="Selesai" <?= $row['status'] == 'Selesai' ? 'selected' : ''; ?>>Tandai: SELESAI</option>
                                </select>
                                <button type="submit" name="update_status" class="btn-update">
                                    <i class="fa-solid fa-paper-plane"></i> Update Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="complaint-card" style="display:block; text-align:center; padding:40px; color:#aaa; border-left:none;">
                <i class="fa-solid fa-mug-hot" style="font-size:40px; margin-bottom:15px;"></i>
                <p>Semua keluhan sudah tertangani. Santai dulu sejenak!</p>
            </div>
        <?php endif; ?>

        <!-- BAGIAN 2: RIWAYAT KELUHAN (TABLE MODE) -->
        <h3 class="section-title"><i class="fa-solid fa-check-double"></i> Riwayat Keluhan Selesai</h3>
        <div class="history-card">
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Penyewa</th>
                            <th>Isi Keluhan</th>
                            <th>Tanggapan Selesai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($hist = mysqli_fetch_assoc($riwayat_keluhan)): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($hist['nama']); ?></strong><br><small>Kamar <?= $hist['nomor_kamar']; ?></small></td>
                            <td style="max-width:300px; color:#666; font-size:13px;"><?= htmlspecialchars($hist['isi_keluhan']); ?></td>
                            <td style="max-width:300px; color:#27ae60; font-weight:500;"><?= htmlspecialchars($hist['tanggapan_admin']); ?></td>
                            <td>
                                <a href="?hapus=<?= $hist['id_keluhan']; ?>" onclick="return confirm('Hapus riwayat ini?')" style="color:#eb5757; font-size:18px;">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if(mysqli_num_rows($riwayat_keluhan) == 0): ?>
                            <tr><td colspan="5" style="text-align:center; padding:20px; color:#999;">Belum ada riwayat keluhan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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