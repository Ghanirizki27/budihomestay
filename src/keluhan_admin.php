<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "admin_nav.php";

requireRole('admin');
ensureAppSchema($conn);

if (isset($_POST['update_status'])) {
    $idKeluhan = (int) $_POST['id_keluhan'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $tanggapan = mysqli_real_escape_string($conn, trim($_POST['tanggapan_admin']));
    mysqli_query($conn, "
        UPDATE laporan_keluhan
        SET status='$status', tanggapan_admin='$tanggapan'
        WHERE id_keluhan='$idKeluhan'
    ");
    header("Location: keluhan_admin.php");
    exit;
}

$keluhan = mysqli_query($conn, "
    SELECT laporan_keluhan.*, penyewa.nama, kamar.nomor_kamar
    FROM laporan_keluhan
    LEFT JOIN penyewa ON laporan_keluhan.id_penyewa = penyewa.id_penyewa
    LEFT JOIN kamar ON penyewa.id_kamar = kamar.id_kamar
    ORDER BY laporan_keluhan.id_keluhan DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keluhan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        .header { background: linear-gradient(90deg, #4da6ff, #2f80ed); color: white; padding: 20px; display: flex; align-items: center; gap: 14px; }
        .menu-icon { cursor: pointer; padding: 10px; border-radius: 10px; }
        .menu-icon:hover { background: rgba(47,128,237,0.1); }
        .card { margin-top: 24px; padding: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #eef2f7; vertical-align: top; }
        th { background: #f3f7fd; color: #103a70; }
        select, button, textarea { padding: 10px 12px; border-radius: 8px; border: 1px solid #ccd7e5; font-family: inherit; }
        textarea { width: 100%; min-height: 90px; resize: vertical; box-sizing: border-box; margin-top: 10px; }
        button { background: #2f80ed; color: white; border: none; cursor: pointer; }
        .overlay { position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; top: 0; left: 0; }
        .overlay.active { display: block; }
        @media (max-width: 768px) { .sidebar { width: 70%; left: -70%; } .main.shift { margin-left: 0; } }
    </style>
</head>
<body>
<?php renderAdminSidebar('keluhan_admin.php'); ?>
<div class="main" id="main">
    <div class="header">
        <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
        <div>
            <h2 style="margin:0;">Laporan Keluhan</h2>
            <p style="margin:4px 0 0; color:#637892;">Keluhan dari penyewa dan status penanganannya.</p>
        </div>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Kamar</th>
                <th>Tanggal</th>
                <th>Keluhan</th>
                <th>Status</th>
                <th>Komentar Admin</th>
                <th>Aksi</th>
            </tr>
            <?php $no = 1; while ($row = mysqli_fetch_assoc($keluhan)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['nama'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($row['nomor_kamar'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($row['tanggal'] ?: '-'); ?></td>
                    <td style="text-align:left;"><?= nl2br(htmlspecialchars($row['isi_keluhan'] ?: '-')); ?></td>
                    <td><?= htmlspecialchars($row['status']); ?></td>
                    <td style="text-align:left;"><?= nl2br(htmlspecialchars($row['tanggapan_admin'] ?: '-')); ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="id_keluhan" value="<?= (int) $row['id_keluhan']; ?>">
                            <select name="status">
                                <option value="Diajukan" <?= $row['status'] === 'Diajukan' ? 'selected' : ''; ?>>Diajukan</option>
                                <option value="Diproses" <?= $row['status'] === 'Diproses' ? 'selected' : ''; ?>>Diproses</option>
                                <option value="Selesai" <?= $row['status'] === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                            <textarea name="tanggapan_admin" placeholder="Tulis komentar atau tindak lanjut admin..."><?= htmlspecialchars($row['tanggapan_admin'] ?: ''); ?></textarea>
                            <button type="submit" name="update_status">Simpan</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if ($no === 1): ?>
                <tr><td colspan="8">Belum ada laporan keluhan.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<script>
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const main = document.getElementById("main");
function syncSidebarLayout(){ if (window.innerWidth > 768 && sidebar.classList.contains("active")) { main.classList.add("shift"); overlay.classList.remove("active"); return; } main.classList.remove("shift"); }
function toggleSidebar(){ sidebar.classList.toggle("active"); if (window.innerWidth <= 768) { overlay.classList.toggle("active"); } else { overlay.classList.remove("active"); } syncSidebarLayout(); }
function closeSidebar(){ sidebar.classList.remove("active"); overlay.classList.remove("active"); syncSidebarLayout(); }
window.addEventListener("resize", syncSidebarLayout); syncSidebarLayout();
</script>
</body>
</html>
