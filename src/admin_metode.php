<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "admin_nav.php";

requireRole('admin');
ensureAppSchema($conn);

// --- 1. PROSES SIMPAN / UPDATE ---
if (isset($_POST['simpan_metode'])) {
    $id = (int)$_POST['id_metode'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_metode']);
    $nomor = mysqli_real_escape_string($conn, $_POST['nomor_tujuan']);
    $an = mysqli_real_escape_string($conn, $_POST['atas_nama']);
    $ikon = mysqli_real_escape_string($conn, $_POST['ikon']);

    if ($id > 0) {
        // Mode Update
        mysqli_query($conn, "UPDATE metode_pembayaran SET nama_metode='$nama', nomor_tujuan='$nomor', atas_nama='$an', ikon='$ikon' WHERE id_metode='$id'");
        $msg = "Metode berhasil diperbarui!";
    } else {
        // Mode Tambah Baru
        mysqli_query($conn, "INSERT INTO metode_pembayaran (nama_metode, nomor_tujuan, atas_nama, ikon) VALUES ('$nama', '$nomor', '$an', '$ikon')");
        $msg = "Metode baru berhasil ditambahkan!";
    }
    header("Location: admin_metode.php?pesan=" . urlencode($msg));
    exit;
}

// --- 2. PROSES HAPUS ---
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM metode_pembayaran WHERE id_metode='$id'");
    header("Location: admin_metode.php?pesan=Metode berhasil dihapus");
    exit;
}

// --- 3. AMBIL DATA UNTUK FORM EDIT (JIKA ADA) ---
$edit_data = ['id_metode' => 0, 'nama_metode' => '', 'nomor_tujuan' => '', 'atas_nama' => '', 'ikon' => 'fa-wallet'];
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $q_edit = mysqli_query($conn, "SELECT * FROM metode_pembayaran WHERE id_metode='$id_edit'");
    if (mysqli_num_rows($q_edit) > 0) {
        $edit_data = mysqli_fetch_assoc($q_edit);
    }
}

// --- 4. AMBIL SEMUA DATA METODE ---
$all_metode = mysqli_query($conn, "SELECT * FROM metode_pembayaran ORDER BY id_metode ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pembayaran - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #eaf3ff, #f8fbff); color: #13355f; }
        
        /* SIDEBAR KONSISTEN */
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
        
        /* HEADER */
        .header { background: linear-gradient(120deg, #1f4f8f, #2f80ed); color: white; border-radius: 24px; padding: 28px; display: flex; align-items: center; justify-content: space-between; gap: 20px; box-shadow: 0 18px 35px rgba(47,128,237,0.25); }
        .menu-icon { cursor: pointer; padding: 10px; border-radius: 10px; font-size: 20px; background: rgba(255,255,255,0.15); display: flex; }

        /* CARDS & FORM */
        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; margin-top: 24px; }
        .card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); border: none; }
        .card h3 { margin-top: 0; color: #0f3c74; font-size: 18px; border-bottom: 2px solid #f0f4f8; padding-bottom: 12px; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: 700; color: #5c6f87; margin-bottom: 8px; }
        input, select { width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #ccd7e5; box-sizing: border-box; font-family: inherit; }
        
        .btn-simpan { width: 100%; padding: 14px; border: none; border-radius: 12px; background: #2f80ed; color: white; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-simpan:hover { background: #1f6fd6; transform: translateY(-2px); }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f8fbff; padding: 15px; color: #5c6f87; font-size: 12px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f0f4f8; font-size: 14px; }
        
        .action-links a { text-decoration: none; font-weight: 700; margin-right: 15px; font-size: 13px; }
        .link-edit { color: #2f80ed; }
        .link-hapus { color: #eb5757; }

        .alert { padding: 15px; border-radius: 12px; background: #e8f8ee; color: #1c854a; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #27ae60; }

        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } .main.shift { margin-left: 0; } .header { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderAdminSidebar('admin_metode.php'); ?>

    <div class="main" id="main">
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1 style="font-size: 24px; margin:0;">Metode Pembayaran</h1>
                    <p style="margin:5px 0 0; opacity:0.8; font-size: 14px;">Kelola nomor rekening dan akun e-wallet untuk pembayaran penyewa.</p>
                </div>
            </div>
            <div style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600;">Level: Administrator</div>
            </div>
        </div>

        <?php if(isset($_GET['pesan'])): ?>
            <div class="alert" style="margin-top:24px;"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_GET['pesan']); ?></div>
        <?php endif; ?>

        <div class="grid">
            <!-- FORM TAMBAH/EDIT -->
            <div class="card">
                <h3><?= ($edit_data['id_metode'] > 0) ? 'Edit Metode' : 'Tambah Metode'; ?></h3>
                <form method="POST">
                    <input type="hidden" name="id_metode" value="<?= $edit_data['id_metode']; ?>">
                    
                    <div class="form-group">
                        <label>Nama Metode (Misal: BRI, DANA)</label>
                        <input type="text" name="nama_metode" value="<?= $edit_data['nama_metode']; ?>" placeholder="Contoh: Transfer Bank BRI" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nomor Tujuan (No. Rek / No. HP)</label>
                        <input type="text" name="nomor_tujuan" value="<?= $edit_data['nomor_tujuan']; ?>" placeholder="Contoh: 0021-01-xxxx" required>
                    </div>

                    <div class="form-group">
                        <label>Atas Nama (A/N)</label>
                        <input type="text" name="atas_nama" value="<?= $edit_data['atas_nama']; ?>" placeholder="Contoh: Budi Homestay" required>
                    </div>

                    <div class="form-group">
                        <label>Ikon (FontAwesome)</label>
                        <select name="ikon">
                            <option value="fa-wallet" <?= $edit_data['ikon'] == 'fa-wallet' ? 'selected' : ''; ?>>Dompet (Default)</option>
                            <option value="fa-bank" <?= $edit_data['ikon'] == 'fa-bank' ? 'selected' : ''; ?>>Bank / Gedung</option>
                            <option value="fa-mobile-screen" <?= $edit_data['ikon'] == 'fa-mobile-screen' ? 'selected' : ''; ?>>Smartphone (E-Wallet)</option>
                            <option value="fa-credit-card" <?= $edit_data['ikon'] == 'fa-credit-card' ? 'selected' : ''; ?>>Kartu Kredit</option>
                        </select>
                    </div>

                    <button type="submit" name="simpan_metode" class="btn-simpan">
                        <i class="fa-solid fa-save"></i> <?= ($edit_data['id_metode'] > 0) ? 'Simpan Perubahan' : 'Tambah Metode'; ?>
                    </button>
                    <?php if($edit_data['id_metode'] > 0): ?>
                        <a href="admin_metode.php" style="display:block; text-align:center; margin-top:15px; color:#666; font-size:13px; text-decoration:none;">Batal Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- TABEL DAFTAR METODE -->
            <div class="card">
                <h3>Daftar Metode Aktif</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Metode</th>
                                <th>Nomor Tujuan</th>
                                <th>Atas Nama</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($all_metode)): ?>
                            <tr>
                                <td><i class="fa-solid <?= $row['ikon']; ?>" style="color:#2f80ed; margin-right:8px;"></i> <strong><?= $row['nama_metode']; ?></strong></td>
                                <td><code><?= $row['nomor_tujuan']; ?></code></td>
                                <td><?= $row['atas_nama']; ?></td>
                                <td class="action-links">
                                    <a href="admin_metode.php?edit=<?= $row['id_metode']; ?>" class="link-edit"><i class="fa-solid fa-pen"></i> Edit</a>
                                    <a href="admin_metode.php?hapus=<?= $row['id_metode']; ?>" class="link-hapus" onclick="return confirm('Hapus metode ini?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
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