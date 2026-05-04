<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "admin_nav.php";

requireRole('admin');
ensureAppSchema($conn);

$id = (int)$_GET['id'];

// Ambil data penyewa gabung dengan data login di tabel admin
$query = mysqli_query($conn, "
    SELECT p.*, a.username 
    FROM penyewa p
    LEFT JOIN admin a ON p.id_penyewa = a.id_penyewa AND a.role = 'penyewa'
    WHERE p.id_penyewa = '$id'
");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("Location: penghuni.php");
    exit;
}

// Ambil daftar kamar (Gunakan nomor_kamar sesuai ledger koreksi kamu)
$kamar_list = mysqli_query($conn, "SELECT * FROM kamar WHERE status = 'Kosong' OR id_kamar = '".$data['id_kamar']."' ORDER BY nomor_kamar ASC");

if (isset($_POST['update'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $ktp_nomor = mysqli_real_escape_string($conn, $_POST['ktp_nomor']);
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $pekerjaan = mysqli_real_escape_string($conn, $_POST['pekerjaan']);
    $id_kamar_baru = mysqli_real_escape_string($conn, $_POST['id_kamar']);
    $tgl_masuk = mysqli_real_escape_string($conn, $_POST['tgl_masuk']);
    $status_sewa = mysqli_real_escape_string($conn, $_POST['status_sewa']);
    
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $id_kamar_lama = $data['id_kamar'];
    $foto_ktp = $data['foto_ktp'];

    if ($_FILES['foto_ktp']['name'] != "") {
        $target_dir = "uploads/ktp/";
        $ext = pathinfo($_FILES["foto_ktp"]["name"], PATHINFO_EXTENSION);
        $file_name = "KTP_" . time() . "_" . $id . "." . $ext;
        move_uploaded_file($_FILES["foto_ktp"]["tmp_name"], $target_dir . $file_name);
        $foto_ktp = $target_dir . $file_name;
    }

    mysqli_query($conn, "UPDATE penyewa SET 
        nama = '$nama', nomor_telepon = '$no_hp', nomor_ktp = '$ktp_nomor', 
        jenis_kelamin = '$jenis_kelamin', alamat = '$alamat', pekerjaan = '$pekerjaan',
        id_kamar = '$id_kamar_baru', tanggal_masuk = '$tgl_masuk', status_sewa = '$status_sewa',
        foto_ktp = '$foto_ktp'
        WHERE id_penyewa = '$id'
    ");

    $sql_admin = "UPDATE admin SET username = '$username'";
    if (!empty($password)) {
        $pass_md5 = md5($password);
        $sql_admin .= ", password = '$pass_md5'";
    }
    $sql_admin .= " WHERE id_penyewa = '$id' AND role = 'penyewa'";
    mysqli_query($conn, $sql_admin);

    if ($id_kamar_baru != $id_kamar_lama) {
        mysqli_query($conn, "UPDATE kamar SET status = 'Kosong' WHERE id_kamar = '$id_kamar_lama'");
        mysqli_query($conn, "UPDATE kamar SET status = 'Terisi' WHERE id_kamar = '$id_kamar_baru'");
    }
    
    if ($status_sewa != 'Aktif') {
        mysqli_query($conn, "UPDATE kamar SET status = 'Kosong' WHERE id_kamar = '$id_kamar_baru'");
    }

    $_SESSION['flash_penyewa'] = "Data penyewa $nama berhasil diperbarui!";
    header("Location: penghuni.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Penyewa - Budi Homestay</title>
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

        .header-box { background: linear-gradient(90deg, #3a8ef6, #6fa3ef); color: white; border-radius: 15px; padding: 25px; margin-bottom: 25px; box-shadow: 0 8px 20px rgba(58, 142, 246, 0.2); }
        .header-box h1 { margin: 0; font-size: 24px; }
        .header-box p { margin: 5px 0 0; opacity: 0.9; font-size: 14px; }

        .card { background: white; border-radius: 20px; padding: 35px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); }
        .form-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
        .form-header h2 { margin: 0; font-size: 18px; color: #102a43; }
        .btn-back { color: #3a8ef6; text-decoration: none; font-weight: 600; font-size: 14px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 18px; }
        .full-width { grid-column: span 2; }
        
        label { display: block; font-size: 14px; font-weight: 700; color: #0f3c74; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 12px 15px; border-radius: 10px; border: 1px solid #ccd7e5; box-sizing: border-box; font-family: inherit; font-size: 14px; transition: 0.3s; }
        input:focus, select:focus, textarea:focus { border-color: #3a8ef6; outline: none; box-shadow: 0 0 0 4px rgba(58, 142, 246, 0.1); }
        
        /* STYLE KHUSUS INPUT PASSWORD */
        .password-container { position: relative; }
        .password-container i { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #637892; font-size: 18px; }

        .btn-update { background: #3a8ef6; color: white; border: none; padding: 12px 25px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-update:hover { background: #2f80ed; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(58, 142, 246, 0.3); }

        @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } .main.shift { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderAdminSidebar('penghuni.php'); ?>

    <div class="main" id="main">
        <div class="header-box">
            <h1>Edit Penyewa</h1>
            <p>Perbarui identitas penyewa, kamar, dan akun login penyewa</p>
        </div>

        <div class="card">
            <div class="form-header">
                <h2>Form Pembaruan Data Penyewa</h2>
                <a href="penghuni.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($data['nama']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nomor KTP</label>
                        <input type="text" name="ktp_nomor" value="<?= htmlspecialchars($data['nomor_ktp']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Update Foto KTP (Biarkan kosong jika tidak ganti)</label>
                        <input type="file" name="foto_ktp" accept="image/*">
                        <?php if($data['foto_ktp']): ?>
                            <small style="color:#2f80ed; font-weight:600; display:block; margin-top:5px;">File aktif: <?= basename($data['foto_ktp']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>No. HP</label>
                        <input type="text" name="no_hp" value="<?= htmlspecialchars($data['nomor_telepon']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin">
                            <option value="Laki-laki" <?= $data['jenis_kelamin'] == 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="Perempuan" <?= $data['jenis_kelamin'] == 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status Sewa</label>
                        <select name="status_sewa">
                            <option value="Aktif" <?= $data['status_sewa'] == 'Aktif' ? 'selected' : ''; ?>>Aktif (Menghuni)</option>
                            <option value="Tidak Aktif" <?= $data['status_sewa'] == 'Tidak Aktif' ? 'selected' : ''; ?>>Tidak Aktif (Keluar)</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label>Alamat Lengkap (Sesuai KTP)</label>
                        <textarea name="alamat" rows="3"><?= htmlspecialchars($data['alamat']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Pekerjaan</label>
                        <input type="text" name="pekerjaan" value="<?= htmlspecialchars($data['pekerjaan']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Username Login Penyewa</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($data['username'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Pilih Kamar</label>
                        <select name="id_kamar" required>
                            <?php while($k = mysqli_fetch_assoc($kamar_list)): ?>
                                <option value="<?= $k['id_kamar']; ?>" <?= $k['id_kamar'] == $data['id_kamar'] ? 'selected' : ''; ?>>
                                    Kamar <?= $k['kode_kamar']; ?> (Rp <?= number_format($k['harga']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Masuk</label>
                        <input type="date" name="tgl_masuk" value="<?= $data['tanggal_masuk']; ?>" required>
                    </div>

                    <!-- FITUR LIHAT PASSWORD BARU -->
                    <div class="form-group full-width">
                        <label>Ganti Password Login (Kosongkan jika tidak ganti)</label>
                        <div class="password-container">
                            <input type="password" name="password" id="password" placeholder="Masukkan password baru jika ingin merubahnya">
                            <i class="fa-solid fa-eye" id="togglePassword"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" name="update" class="btn-update">
                    Simpan Perubahan Data
                </button>
            </form>
        </div>
    </div>

    <script>
    // Logika Toggle Password
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    togglePassword.addEventListener('click', function () {
        const type = password.type === 'password' ? 'text' : 'password';
        password.type = type;
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

    function toggleSidebar() { document.getElementById("sidebar").classList.toggle("active"); if (window.innerWidth <= 900) { document.getElementById("overlay").classList.toggle("active"); } }
    function closeSidebar() { document.getElementById("sidebar").classList.remove("active"); document.getElementById("overlay").classList.remove("active"); }
    </script>
</body>
</html>