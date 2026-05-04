<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "admin_nav.php";

requireRole('admin');
ensureAppSchema($conn);

/* ===============================
   PROSES TAMBAH PENGUMUMAN
=================================*/
if (isset($_POST['tambah'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi = mysqli_real_escape_string($conn, $_POST['isi']);
    $id_admin = (int) ($_SESSION['id_admin'] ?? 0);

    $query_tambah = mysqli_query($conn, "
        INSERT INTO pengumuman (id_admin, judul, isi)
        VALUES ('$id_admin', '$judul', '$isi')
    ");

    if ($query_tambah) {
        header("Location: pengumuman.php?pesan=berhasil");
    } else {
        header("Location: pengumuman.php?pesan=gagal");
    }
    exit;
}

/* ===============================
   PROSES HAPUS PENGUMUMAN
=================================*/
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM pengumuman WHERE id_pengumuman = '$id'");
    header("Location: pengumuman.php?pesan=terhapus");
    exit;
}

$data_pengumuman = mysqli_query($conn, "SELECT * FROM pengumuman ORDER BY tanggal_dibuat DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #eaf3ff, #f8fbff); color: #13355f; }
        
        /* SIDEBAR */
        .sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #0f2f59, #123d75); position: fixed; left: -250px; top: 0; transition: 0.3s; z-index: 1000; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar.active { left: 0; }
        .sidebar h2 { color: white; text-align: center; padding: 20px; margin: 0; font-size: 22px; }
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
        
        /* CARDS */
        .card { background: white; border-radius: 20px; padding: 24px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); margin-top: 24px; border: none; }
        .card h3 { margin-top: 0; color: #0f3c74; font-size: 18px; border-bottom: 2px solid #f0f4f8; padding-bottom: 10px; margin-bottom: 20px; }
        
        /* FORM ELEMENTS */
        input[type="text"], textarea { width: 100%; padding: 12px 15px; border-radius: 12px; border: 1px solid #ccd7e5; box-sizing: border-box; font-family: inherit; margin-bottom: 15px; transition: 0.3s; }
        input[type="text"]:focus, textarea:focus { border-color: #2f80ed; outline: none; box-shadow: 0 0 0 4px rgba(47,128,237,0.1); }
        .btn-kirim { padding: 12px 25px; border: none; border-radius: 12px; background: linear-gradient(90deg, #4da6ff, #2f80ed); color: white; font-weight: 700; cursor: pointer; transition: 0.3s; box-shadow: 0 8px 15px rgba(47,128,237,0.2); }
        .btn-kirim:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(47,128,237,0.3); }

        /* TABLES */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f8fbff; padding: 15px; color: #5c6f87; font-size: 13px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f0f4f8; font-size: 14px; vertical-align: middle; }
        
        .btn-hapus { color: #eb5757; text-decoration: none; font-weight: 600; padding: 8px 12px; border-radius: 8px; transition: 0.2s; }
        .btn-hapus:hover { background: #fff1f1; }
        
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; background: #d4edda; color: #155724; font-weight: 600; border-left: 5px solid #28a745; margin-top: 24px; }

        @media (max-width: 900px) {
            .sidebar { width: 70%; left: -70%; }
            .main.shift { margin-left: 0; }
            .header { flex-direction: column; align-items: flex-start; }
            .header-info-right { text-align: left !important; margin-top: 15px; }
        }
    </style>
</head>
<body>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderAdminSidebar('pengumuman.php'); ?>

    <div class="main" id="main">
        <!-- HEADER KONSISTEN -->
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1 style="font-size: 24px;">Pengumuman</h1>
                    <p>Bagikan informasi terbaru, peraturan, atau berita kepada seluruh penyewa.</p>
                </div>
            </div>
            
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600;">Level: Administrator</div>
            </div>
        </div>

        <?php if(isset($_GET['pesan']) && $_GET['pesan'] == "berhasil"): ?>
            <div class="alert"><i class="fa-solid fa-circle-check"></i> Pengumuman Berhasil Dikirim!</div>
        <?php endif; ?>

        <!-- FORM BUAT PENGUMUMAN -->
        <div class="card">
            <h3><i class="fa-solid fa-bullhorn" style="color: #2f80ed;"></i> Buat Pengumuman Baru</h3>
            <form method="POST">
                <input type="text" name="judul" placeholder="Judul Pengumuman (Contoh: Jadwal Kebersihan)" required>
                <textarea name="isi" rows="5" placeholder="Tulis rincian pengumuman yang ingin disampaikan ke penyewa..." required></textarea>
                <button type="submit" name="tambah" class="btn-kirim">
                    <i class="fa-solid fa-paper-plane"></i> Simpan & Kirim Sekarang
                </button>
            </form>
        </div>

        <!-- TABEL RIWAYAT -->
        <div class="card">
            <h3><i class="fa-solid fa-history"></i> Riwayat Pengumuman</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Judul</th>
                            <th>Isi Pengumuman</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data_pengumuman)): ?>
                        <tr>
                            <td style="white-space: nowrap; color: #5c6f87;"><small><?= date('d M Y, H:i', strtotime($row['tanggal_dibuat'])); ?></small></td>
                            <td><strong><?= htmlspecialchars($row['judul']); ?></strong></td>
                            <td style="color: #666;"><?= htmlspecialchars(mb_strimwidth($row['isi'], 0, 80, '...')); ?></td>
                            <td>
                                <a href="pengumuman.php?hapus=<?= (int) $row['id_pengumuman']; ?>"
                                   class="btn-hapus"
                                   onclick="return confirm('Hapus pengumuman ini?')">
                                    <i class="fa-solid fa-trash-can"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if(mysqli_num_rows($data_pengumuman) == 0): ?>
                            <tr><td colspan="4" style="text-align: center; color: #888; padding: 30px;">Belum ada pengumuman yang dikirim.</td></tr>
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

    window.addEventListener("resize", syncSidebarLayout);
    syncSidebarLayout();
    </script>
</body>
</html>