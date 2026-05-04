<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "admin_nav.php";
include_once "tenant_context.php"; 

requireRole('admin');
ensureAppSchema($conn);

// --- 1. PROSES VALIDASI PEMBAYARAN OLEH ADMIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_validasi'])) {
    $id_pembayaran = (int) $_POST['id_pembayaran'];
    $aksi = mysqli_real_escape_string($conn, $_POST['aksi_validasi']); 
    
    $query_update = "UPDATE pembayaran SET status = '$aksi' WHERE id_pembayaran = '$id_pembayaran'";
    if (mysqli_query($conn, $query_update)) {
        header("Location: pembayaran.php?pesan=berhasil");
        exit;
    } else {
        echo "<script>alert('Gagal memproses validasi pembayaran!');</script>";
    }
}

// --- 2. QUERY DATA STATUS TAGIHAN PENYEWA AKTIF ---
$query_tagihan = mysqli_query($conn, "
    SELECT py.id_penyewa, py.nama, k.nomor_kamar AS kode_kamar, py.tanggal_masuk, k.harga 
    FROM penyewa py
    JOIN kamar k ON py.id_kamar = k.id_kamar
    WHERE py.status_sewa = 'Aktif'
    ORDER BY k.nomor_kamar ASC
");

// --- 3. QUERY DATA MENUNGGU VALIDASI ---
$query_pending = mysqli_query($conn, "
    SELECT p.*, py.nama, k.nomor_kamar AS kode_kamar 
    FROM pembayaran p
    JOIN penyewa py ON p.id_penyewa = py.id_penyewa
    JOIN kamar k ON py.id_kamar = k.id_kamar
    WHERE p.status = 'Menunggu Validasi'
    ORDER BY p.tanggal_bayar ASC
");

// --- 4. QUERY RIWAYAT PEMBAYARAN ---
$query_riwayat = mysqli_query($conn, "
    SELECT p.*, py.nama, k.nomor_kamar AS kode_kamar 
    FROM pembayaran p
    JOIN penyewa py ON p.id_penyewa = py.id_penyewa
    JOIN kamar k ON py.id_kamar = k.id_kamar
    WHERE p.status != 'Menunggu Validasi'
    ORDER BY p.tanggal_bayar DESC 
    LIMIT 50
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Sewa - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #eaf3ff, #f8fbff); color: #13355f; }
        
        /* SIDEBAR */
        .sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #0f2f59, #123d75); position: fixed; left: -250px; top: 0; transition: 0.3s; z-index: 1000; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar.active { left: 0; }
        .sidebar h2 { color: white; text-align: center; padding: 20px; margin: 0; }
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #dbe9ff; text-decoration: none; }
        .sidebar a.active { background: rgba(255,255,255,0.14); color: #ffffff; margin: 6px 12px; border-radius: 14px; }
        
        /* LOGOUT LEBIH GELAP SESUAI REQUEST */
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
        
        /* TABLES */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f8fbff; padding: 15px; color: #5c6f87; font-size: 13px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f0f4f8; font-size: 14px; vertical-align: middle; }
        
        /* STATUS CHIPS */
        .status-chip { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; text-align: center; }
        .status-aktif { background: #e8f8ee; color: #1c854a; }
        .status-telat { background: #ffe7e7; color: #c23d3d; }
        .status-ok { background: #e8f8ee; color: #1c854a; }
        .status-due { background: #fff4db; color: #a86c00; }
        .status-late { background: #ffe7e7; color: #c23d3d; }
        
        /* BUTTONS */
        .btn { padding: 8px 14px; border-radius: 10px; border: none; font-size: 13px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; color: white; transition: 0.2s; }
        .btn-info { background: #4da6ff; } .btn-info:hover { background: #2f80ed; }
        .btn-success { background: #27ae60; } .btn-success:hover { background: #219150; }
        .btn-danger { background: #eb5757; } .btn-danger:hover { background: #cf4949; }
        
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; background: #d4edda; color: #155724; font-weight: 600; border-left: 5px solid #28a745; margin-top: 24px; }

        /* MODAL POPUP GAMBAR */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .modal-content { max-width: 80%; max-height: 80%; border-radius: 15px; box-shadow: 0 0 30px rgba(0,0,0,0.5); animation: zoomIn 0.3s ease; }
        .close-modal { position: absolute; top: 30px; right: 40px; color: white; font-size: 40px; font-weight: bold; cursor: pointer; }
        @keyframes zoomIn { from {transform:scale(0.8); opacity:0;} to {transform:scale(1); opacity:1;} }

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
    <?php renderAdminSidebar('pembayaran.php'); ?>

    <div class="main" id="main">
        <!-- HEADER KONSISTEN -->
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1 style="font-size: 24px;">Pembayaran Sewa</h1>
                    <p>Kelola verifikasi bukti transfer dan pantau status tagihan penyewa.</p>
                </div>
            </div>
            
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600;">Level: Administrator</div>
            </div>
        </div>

        <?php if(isset($_GET['pesan']) && $_GET['pesan'] == "berhasil"): ?>
            <div class="alert"><i class="fa-solid fa-circle-check"></i> Pembayaran berhasil divalidasi!</div>
        <?php endif; ?>

        <!-- 1. TABEL MENUNGGU VALIDASI -->
        <div class="card" style="border-left: 6px solid #f1c40f;">
            <h3><i class="fa-solid fa-bell" style="color:#f1c40f;"></i> Antrean Verifikasi Bukti Transfer</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tgl Kirim</th>
                            <th>Penyewa</th>
                            <th>Kamar</th>
                            <th>Metode</th>
                            <th>Jumlah</th>
                            <th>Bukti</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_pending) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($query_pending)): ?>
                            <tr>
                                <td><?= date('d/m/y H:i', strtotime($row['tanggal_bayar'])); ?></td>
                                <td><strong><?= htmlspecialchars($row['nama']); ?></strong></td>
                                <td><?= htmlspecialchars($row['kode_kamar']); ?></td>
                                <td><?= htmlspecialchars($row['metode_pembayaran']); ?></td>
                                <td style="color: #2f80ed; font-weight: bold;">Rp <?= number_format($row['jumlah']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-info" onclick="bukaModal('uploads/bukti/<?= htmlspecialchars($row['bukti_pembayaran']); ?>')">
                                       <i class="fa-solid fa-image"></i> Lihat
                                    </button>
                                </td>
                                <td>
                                    <form action="" method="POST" style="display: flex; gap: 8px; margin:0;">
                                        <input type="hidden" name="id_pembayaran" value="<?= $row['id_pembayaran']; ?>">
                                        <button type="submit" name="aksi_validasi" value="Lunas" class="btn btn-success"><i class="fa-solid fa-check"></i></button>
                                        <button type="submit" name="aksi_validasi" value="Ditolak" class="btn btn-danger" onclick="return confirm('Tolak pembayaran ini?')"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; color: #888; padding: 25px;">Tidak ada antrean pembayaran saat ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. STATUS TAGIHAN -->
        <div class="card">
            <h3><i class="fa-solid fa-user-check"></i> Status Tagihan Penyewa Aktif</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Kamar</th>
                            <th>Tgl Masuk</th>
                            <th>Tagihan</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query_tagihan)): 
                            $summary = getTenantPaymentSummary($conn, $row['id_penyewa'], $row['tanggal_masuk']); ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($row['nama']); ?></strong></td>
                            <td><?= htmlspecialchars($row['kode_kamar']); ?></td>
                            <td><?= date('d/m/y', strtotime($row['tanggal_masuk'])); ?></td>
                            <td>Rp <?= number_format($row['harga']); ?></td>
                            <td><span class="status-chip <?= htmlspecialchars($summary['status_class']); ?>"><?= htmlspecialchars($summary['status_label']); ?></span></td>
                            <td>
                                <?php if (strpos($summary['status_class'], 'late') !== false || strpos($summary['status_class'], 'due') !== false): ?>
                                    <span style="color: #eb5757; font-weight:bold; font-size:12px;"><i class="fa-solid fa-triangle-exclamation"></i> Segera Tagih</span>
                                <?php else: ?>
                                    <span style="color: #6e7f95; font-size:12px;">Aman (<?= htmlspecialchars($summary['jatuh_tempo']); ?>)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. RIWAYAT -->
        <div class="card">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pembayaran Terakhir</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Penyewa</th>
                            <th>Kamar</th>
                            <th>Metode</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($query_riwayat)): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($row['tanggal_bayar'])); ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= htmlspecialchars($row['kode_kamar']); ?></td>
                            <td><?= htmlspecialchars($row['metode_pembayaran'] ?: 'Manual'); ?></td>
                            <td>Rp <?= number_format($row['jumlah']); ?></td>
                            <td>
                                <span class="status-chip <?= $row['status'] == 'Lunas' ? 'status-aktif' : 'status-telat'; ?>">
                                    <?= htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL BUKTI BAYAR -->
    <div id="imageModal" class="modal">
        <span class="close-modal" onclick="tutupModal()">&times;</span>
        <img class="modal-content" id="imgBukti">
    </div>

    <script>
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const main = document.getElementById("main");
    const modal = document.getElementById("imageModal");

    function syncSidebarLayout() { if (window.innerWidth > 900 && sidebar.classList.contains("active")) { main.classList.add("shift"); return; } main.classList.remove("shift"); }
    function toggleSidebar() { sidebar.classList.toggle("active"); if (window.innerWidth <= 900) { overlay.classList.toggle("active"); } syncSidebarLayout(); }
    function closeSidebar() { sidebar.classList.remove("active"); overlay.classList.remove("active"); syncSidebarLayout(); }

    function updateDateTime() {
        const skrg = new Date();
        document.getElementById("tanggal").textContent = skrg.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        document.getElementById("waktu").textContent = skrg.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setInterval(updateDateTime, 1000); updateDateTime();

    function bukaModal(imgSrc) { modal.style.display = "flex"; document.getElementById("imgBukti").src = imgSrc; }
    function tutupModal() { modal.style.display = "none"; }
    window.onclick = function(event) { if (event.target == modal) { tutupModal(); } }

    window.addEventListener("resize", syncSidebarLayout);
    syncSidebarLayout();
    </script>
</body>
</html>