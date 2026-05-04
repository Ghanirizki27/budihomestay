<?php
include "koneksi.php";
include_once "admin_nav.php";
include_once "auth.php";
include_once "db_migrations.php";

session_start();
requireRole('admin');
ensureAppSchema($conn);

/* ===============================
   PROSES TAMBAH KAMAR
=================================*/
if (isset($_POST['tambah'])) {
    $kode  = mysqli_real_escape_string($conn, $_POST['kode_kamar']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);

    mysqli_query($conn, "
        INSERT INTO kamar (nomor_kamar, harga, status)
        VALUES ('$kode', '$harga', 'Kosong')
    ");

    header("Location: kamar.php?pesan=berhasil");
    exit;
}

/* ===============================
   PROSES EDIT KAMAR
=================================*/
if (isset($_POST['edit_kamar'])) {
    $id    = mysqli_real_escape_string($conn, $_POST['id_kamar']);
    $kode  = mysqli_real_escape_string($conn, $_POST['kode_kamar']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);

    mysqli_query($conn, "
        UPDATE kamar SET nomor_kamar = '$kode', harga = '$harga' 
        WHERE id_kamar = '$id'
    ");

    header("Location: kamar.php?pesan=diperbarui");
    exit;
}

/* ===============================
   PROSES HAPUS KAMAR
=================================*/
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    mysqli_query($conn, "DELETE FROM kamar WHERE id_kamar='$id'");
    header("Location: kamar.php?pesan=terhapus");
    exit;
}

$dataKamar = mysqli_query($conn, "SELECT * FROM kamar ORDER BY nomor_kamar ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kamar - Budi Homestay</title>
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
        
        /* CARDS */
        .card { background: white; border-radius: 20px; padding: 24px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); margin-top: 24px; }
        .card h3 { margin-top: 0; color: #0f3c74; font-size: 18px; border-bottom: 2px solid #f0f4f8; padding-bottom: 10px; margin-bottom: 20px; }
        
        .form-tambah { display: flex; gap: 15px; flex-wrap: wrap; }
        input { flex: 1; min-width: 200px; padding: 12px 15px; border-radius: 12px; border: 1px solid #ccd7e5; font-family: inherit; outline: none; transition: 0.3s; }
        input:focus { border-color: #2f80ed; box-shadow: 0 0 0 3px rgba(47,128,237,0.1); }
        
        .btn-simpan { background: #2f80ed; color: white; border: none; padding: 12px 25px; border-radius: 12px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-simpan:hover { background: #1f6fd6; transform: translateY(-2px); }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f8fbff; padding: 15px; color: #5c6f87; font-size: 14px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f0f4f8; font-size: 15px; vertical-align: middle; }
        
        .status-chip { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
        .status-kosong { background: #e8f8ee; color: #1c854a; }
        .status-terisi { background: #ffe7e7; color: #c23d3d; }
        
        .btn-aksi { text-decoration: none; font-weight: 600; padding: 8px 12px; border-radius: 8px; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; font-size: 14px; }
        .btn-edit { color: #2f80ed; } .btn-edit:hover { background: #f0f7ff; }
        .btn-hapus { color: #eb5757; } .btn-hapus:hover { background: #fff1f1; }
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; background: #d4edda; color: #155724; font-weight: 600; border-left: 5px solid #28a745; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; padding: 30px; border-radius: 24px; width: 90%; max-width: 450px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
            .sidebar { width: 70%; left: -70%; }
            .main.shift { margin-left: 0; }
            .header { flex-direction: column; align-items: flex-start; }
            .header-info-right { text-align: left !important; margin-top: 15px; }
        }
    </style>
</head>
<body>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderAdminSidebar('kamar.php'); ?>

    <div class="main" id="main">
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1 style="font-size: 24px;">Manajemen Kamar</h1>
                    <p>Kelola ketersediaan unit dan harga sewa kamar homestay.</p>
                </div>
            </div>
            
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">Level: Administrator</div>
            </div>
        </div>

        <?php if (isset($_GET['pesan'])): ?>
            <div class="alert" style="margin-top: 24px;">
                <i class="fa-solid fa-circle-check"></i> 
                Data kamar berhasil <?= $_GET['pesan'] == 'berhasil' ? 'ditambahkan' : ($_GET['pesan'] == 'diperbarui' ? 'diperbarui' : 'dihapus'); ?>!
            </div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fa-solid fa-plus-circle"></i> Tambah Kamar Baru</h3>
            <form method="POST" class="form-tambah">
                <input type="text" name="kode_kamar" placeholder="Nomor Kamar (Contoh: A001)" required>
                <input type="number" name="harga" placeholder="Harga Sewa (Rp)" required>
                <button type="submit" name="tambah" class="btn-simpan"><i class="fa-solid fa-save"></i> Simpan Kamar</button>
            </form>
        </div>

        <div class="card">
            <h3><i class="fa-solid fa-list-ul"></i> Daftar Ketersediaan Kamar</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Kamar</th>
                            <th>Harga Sewa</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($row=mysqli_fetch_assoc($dataKamar)){ ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($row['nomor_kamar']); ?></strong></td>
                            <td style="color: #2f80ed; font-weight: bold;">Rp <?= number_format($row['harga']); ?></td>
                            <td>
                                <span class="status-chip <?= $row['status']=='Kosong' ? 'status-kosong' : 'status-terisi'; ?>">
                                    <i class="fa-solid <?= $row['status']=='Kosong' ? 'fa-check' : 'fa-user-lock'; ?>"></i>
                                    <?= $row['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="javascript:void(0)" class="btn-aksi btn-edit" onclick="bukaModalEdit('<?= $row['id_kamar']; ?>', '<?= $row['nomor_kamar']; ?>', '<?= $row['harga']; ?>')">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <a href="?hapus=<?= $row['id_kamar']; ?>" class="btn-aksi btn-hapus" onclick="return confirm('Hapus data kamar ini?')">
                                    <i class="fa-solid fa-trash-can"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div id="modalEdit" class="modal">
        <div class="modal-content">
            <h3 style="margin-top:0; color:#0f3c74;"><i class="fa-solid fa-edit"></i> Edit Data Kamar</h3>
            <form method="POST" action="">
                <input type="hidden" name="id_kamar" id="edit_id">
                <div style="margin-bottom:15px;">
                    <label style="font-size:14px; font-weight:600; color:#5c6f87; display:block; margin-bottom:8px;">Nomor Kamar</label>
                    <input type="text" name="kode_kamar" id="edit_nomor" style="width:100%;" required>
                </div>
                <div style="margin-bottom:25px;">
                    <label style="font-size:14px; font-weight:600; color:#5c6f87; display:block; margin-bottom:8px;">Harga Sewa (Rp)</label>
                    <input type="number" name="harga" id="edit_harga" style="width:100%;" required>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="tutupModalEdit()" style="flex:1; background:#f0f4f8; color:#5c6f87; border:none; padding:12px; border-radius:12px; font-weight:bold; cursor:pointer;">Batal</button>
                    <button type="submit" name="edit_kamar" style="flex:2; background:#2f80ed; color:white; border:none; padding:12px; border-radius:12px; font-weight:bold; cursor:pointer;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const main = document.getElementById("main");
    const modalEdit = document.getElementById("modalEdit");

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

    function bukaModalEdit(id, nomor, harga) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nomor').value = nomor;
        document.getElementById('edit_harga').value = harga;
        modalEdit.style.display = "flex";
    }
    function tutupModalEdit() { modalEdit.style.display = "none"; }
    window.onclick = function(event) { if (event.target == modalEdit) { tutupModalEdit(); } }
    
    window.addEventListener("resize", syncSidebarLayout);
    syncSidebarLayout();
    </script>
</body>
</html>