<?php
session_start();
include_once "auth.php";
requireRole('admin');
include "koneksi.php";
include_once "admin_nav.php";
include_once "db_migrations.php";

ensureAppSchema($conn);

/* =========================
   PROSES HAPUS
========================= */
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);

    // Hapus akun login penyewa
    mysqli_query($conn, "DELETE FROM admin WHERE id_penyewa='$id' AND role='penyewa'");

    // Ambil ID kamar untuk dikosongkan kembali
    $ambil = mysqli_query($conn, "SELECT id_kamar FROM penyewa WHERE id_penyewa='$id'");
    $data = mysqli_fetch_assoc($ambil);

    // Hapus data penyewa
    mysqli_query($conn, "DELETE FROM penyewa WHERE id_penyewa='$id'");

    // Update status kamar menjadi Kosong
    if ($data['id_kamar']) {
        mysqli_query($conn, "UPDATE kamar SET status='Kosong' WHERE id_kamar='".$data['id_kamar']."'");
    }

    header("Location: penghuni.php?pesan=terhapus");
    exit;
}

/* =========================
   AMBIL DATA PENYEWA
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penyewa - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #eaf3ff, #f8fbff); color: #13355f; }
        
        /* SIDEBAR */
        .sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #0f2f59, #123d75); position: fixed; left: -250px; top: 0; transition: 0.3s; z-index: 1000; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar.active { left: 0; }
        .sidebar h2 { color: white; text-align: center; padding: 20px; margin: 0; }
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #dbe9ff; text-decoration: none; }
        .sidebar a.active { background: rgba(255,255,255,0.14); color: #ffffff; margin: 6px 12px; border-radius: 14px; }
        
        /* LOGOUT LEBIH GELAP */
        .menu-bawah a { background: #081b33; } 
        
        .overlay { position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; top: 0; left: 0; z-index: 999; }
        .overlay.active { display: block; }
        
        .main { margin-left: 0; padding: 35px; transition: 0.3s; }
        .main.shift { margin-left: 250px; }
        
        /* HEADER CENTER ALIGNED */
        .header { background: linear-gradient(120deg, #1f4f8f, #2f80ed); color: white; border-radius: 24px; padding: 28px; display: flex; align-items: center; justify-content: space-between; gap: 20px; box-shadow: 0 18px 35px rgba(47,128,237,0.25); }
        .header h1, .header p { margin: 0; }
        .header p { margin-top: 8px; opacity: 0.9; font-size: 14px; }
        .menu-icon { cursor: pointer; padding: 10px; border-radius: 10px; font-size: 20px; background: rgba(255,255,255,0.15); display: flex; align-items: center; }
        
        /* CARD */
        .card { background: white; border-radius: 20px; padding: 24px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); margin-top: 24px; }
        
        /* TOOLBAR */
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 20px; border-bottom: 2px solid #f0f4f8; padding-bottom: 15px; }
        .toolbar h3 { margin: 0; color: #0f3c74; }
        
        .btn-tambah { padding: 12px 20px; border: none; border-radius: 12px; background: #2f80ed; color: white; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-tambah:hover { background: #1f6fd6; transform: translateY(-2px); }

        /* TABLE */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        th { text-align: left; background: #f8fbff; padding: 15px; color: #5c6f87; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 15px; border-bottom: 1px solid #f0f4f8; font-size: 14px; vertical-align: middle; }
        
        .status-pill { padding: 6px 12px; border-radius: 20px; background: #e8f8ee; color: #1c854a; font-weight: 700; font-size: 12px; }
        
        /* BUTTONS */
        .btn-ktp { padding: 6px 12px; border: none; border-radius: 8px; background: #4da6ff; color: white; cursor: pointer; font-size: 12px; font-weight: 600; }
        .btn-aksi { text-decoration: none; font-weight: 600; padding: 8px 12px; border-radius: 8px; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; font-size: 13px; }
        .btn-edit { color: #2f80ed; } .btn-edit:hover { background: #f0f7ff; }
        .btn-hapus { color: #eb5757; } .btn-hapus:hover { background: #fff1f1; }

        /* FLASH MESSAGE */
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; background: #d4edda; color: #155724; font-weight: 600; border-left: 5px solid #28a745; margin-top: 24px; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .modal-content { max-width: 80%; max-height: 80%; border-radius: 15px; box-shadow: 0 0 30px rgba(0,0,0,0.5); }
        .close-modal { position: absolute; top: 30px; right: 40px; color: white; font-size: 40px; font-weight: bold; cursor: pointer; }

        @media (max-width: 900px) { .sidebar { width: 70%; left: -70%; } .main.shift { margin-left: 0; } .header { flex-direction: column; align-items: flex-start; } .header-info-right { text-align: left !important; margin-top: 15px; } }
    </style>
</head>
<body>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderAdminSidebar('penghuni.php'); ?>

    <div class="main" id="main">
        <!-- HEADER KONSISTEN -->
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1 style="font-size: 24px;">Data Penyewa</h1>
                    <p>Pendaftaran calon penyewa dan pengelolaan data sewa aktif.</p>
                </div>
            </div>
            
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600;">Level: Administrator</div>
            </div>
        </div>

        <?php if ($flashPenyewa): ?>
            <div class="alert"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($flashPenyewa); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="toolbar">
                <div>
                    <h3>Daftar Seluruh Penyewa</h3>
                    <p style="margin:5px 0 0; color:#6e7f95; font-size:13px;">Kelola profil, dokumen, dan status sewa penghuni.</p>
                </div>
                <a class="btn-tambah" href="tambah_penyewa.php">
                    <i class="fa-solid fa-user-plus"></i> Tambah Penyewa
                </a>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>No. KTP</th>
                            <th>Dokumen</th>
                            <th>No. HP</th>
                            <th>Kamar</th>
                            <th>Tgl Masuk</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($row = mysqli_fetch_assoc($dataPenyewa)) { ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($row['nama']); ?></strong></td>
                            <td><?= htmlspecialchars($row['nomor_ktp'] ?: '-'); ?></td>
                            <td>
                                <?php if (!empty($row['foto_ktp'])): ?>
                                    <button class="btn-ktp" onclick="showKTP('<?= htmlspecialchars($row['foto_ktp']); ?>')">
                                        <i class="fa-solid fa-image"></i> Lihat KTP
                                    </button>
                                <?php else: ?>
                                    <span style="color:#bbb;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['nomor_telepon']); ?></td>
                            <td><span style="color: #2f80ed; font-weight: bold;"><?= htmlspecialchars($row['nomor_kamar'] ?: '-'); ?></span></td>
                            <td><?= date('d/m/Y', strtotime($row['tanggal_masuk'])); ?></td>
                            <td><span class="status-pill"><?= htmlspecialchars($row['status_sewa']); ?></span></td>
                            <td>
                                <!-- TOMBOL EDIT BARU -->
                                <a href="edit_penyewa.php?id=<?= $row['id_penyewa']; ?>" class="btn-aksi btn-edit">
                                    <i class="fa-solid fa-user-pen"></i> Edit
                                </a>
                                <a href="?hapus=<?= $row['id_penyewa']; ?>" class="btn-aksi btn-hapus" onclick="return confirm('Hapus data penyewa ini? Akun login juga akan terhapus.')">
                                    <i class="fa-solid fa-trash-can"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php if(mysqli_num_rows($dataPenyewa) == 0): ?>
                            <tr><td colspan="9" style="text-align:center; padding:30px; color:#999;">Belum ada data penyewa terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL LIHAT KTP -->
    <div id="ktpModal" class="modal">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="ktpImage" src="" alt="KTP">
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
    setInterval(updateDateTime, 1000);
    updateDateTime();

    function showKTP(src) {
        document.getElementById('ktpImage').src = src;
        document.getElementById('ktpModal').style.display = 'flex';
    }
    function closeModal() { document.getElementById('ktpModal').style.display = 'none'; }
    window.onclick = function(event) { if (event.target == document.getElementById('ktpModal')) { closeModal(); } }

    window.addEventListener("resize", syncSidebarLayout);
    syncSidebarLayout();
    </script>
</body>
</html>