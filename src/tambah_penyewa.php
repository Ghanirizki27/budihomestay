<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "admin_nav.php";

requireRole('admin');
ensureAppSchema($conn);

$errorTambah = null;

if (isset($_POST['tambah'])) {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $ktp      = mysqli_real_escape_string($conn, $_POST['nomor_ktp']);
    $hp       = mysqli_real_escape_string($conn, $_POST['hp']);
    $gender   = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $alamat   = mysqli_real_escape_string($conn, $_POST['alamat']);
    $kerja    = mysqli_real_escape_string($conn, $_POST['pekerjaan']);
    $kamar    = mysqli_real_escape_string($conn, $_POST['kamar']);
    $tgl      = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $status   = mysqli_real_escape_string($conn, $_POST['status_sewa']); // Menangkap Status
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $passwordInput = $_POST['password'];
    $fotoKtp = '';

    $cekUsername = mysqli_query($conn, "SELECT id_admin FROM admin WHERE username='$username' LIMIT 1");
    if (mysqli_num_rows($cekUsername) > 0) {
        $errorTambah = 'Username penyewa sudah dipakai. Gunakan username lain.';
    }

    if (!$errorTambah && !empty($_FILES['foto_ktp']['name'])) {
        $uploadDir = "uploads/ktp/";
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $extension = strtolower(pathinfo($_FILES['foto_ktp']['name'], PATHINFO_EXTENSION));
        $fileName = 'ktp_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $targetPath)) {
            $fotoKtp = $targetPath;
        }
    }

    if (!$errorTambah) {
        // 1. Simpan ke tabel penyewa dengan status dari form
        mysqli_query($conn, "
            INSERT INTO penyewa (nama, nomor_ktp, foto_ktp, nomor_telepon, jenis_kelamin, alamat, pekerjaan, tanggal_masuk, id_kamar, status_sewa)
            VALUES ('$nama', '$ktp', '$fotoKtp', '$hp', '$gender', '$alamat', '$kerja', '$tgl', '$kamar', '$status')
        ");

        $idPenyewa = mysqli_insert_id($conn);
        $passwordHash = md5($passwordInput);

        mysqli_query($conn, "
            INSERT INTO admin (username, password, nama_lengkap, role, id_penyewa)
            VALUES ('$username', '$passwordHash', '$nama', 'penyewa', '$idPenyewa')
        ");

        // Jika status Aktif, maka kamar otomatis 'Terisi'
        if ($status == 'Aktif') {
            mysqli_query($conn, "UPDATE kamar SET status='Terisi' WHERE id_kamar='$kamar'");
        }

        $_SESSION['flash_penyewa'] = "Penyewa $nama berhasil didaftarkan!";
        header("Location: penghuni.php");
        exit;
    }
}

$dataKamarKosong = mysqli_query($conn, "SELECT * FROM kamar WHERE status='Kosong' ORDER BY nomor_kamar ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Penyewa - Budi Homestay</title>
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
        .info-blue { background: #eef5ff; color: #2f80ed; padding: 12px 18px; border-radius: 10px; font-size: 13px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 18px; }
        .full-width { grid-column: span 2; }
        label { display: block; font-size: 14px; font-weight: 700; color: #0f3c74; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 12px 15px; border-radius: 10px; border: 1px solid #ccd7e5; box-sizing: border-box; font-family: inherit; font-size: 14px; transition: 0.3s; }
        input:focus, select:focus, textarea:focus { border-color: #3a8ef6; outline: none; box-shadow: 0 0 0 4px rgba(58, 142, 246, 0.1); }
        .password-container { position: relative; }
        .password-container i { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #637892; font-size: 18px; }
        .btn-submit { background: #3a8ef6; color: white; border: none; padding: 14px 25px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; width: 100%; font-size: 16px; }
        .btn-submit:hover { background: #2f80ed; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(58, 142, 246, 0.3); }
        .alert-error { background: #ffe9e9; color: #b53a3a; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #ff4d4d; }
        @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } .main.shift { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderAdminSidebar('penghuni.php'); ?>

    <div class="main" id="main">
        <div class="header-box">
            <div>
                <h1>Tambah Penyewa</h1>
                <p>Lengkapi identitas calon penyewa, kamar, dan akun login penyewa</p>
            </div>
        </div>

        <div class="card">
            <div class="form-header">
                <div>
                    <h2>Form Pendaftaran Penyewa Baru</h2>
                    <p style="margin:5px 0 0; font-size:13px; color:#6e7f95;">Data dibuat terpisah agar halaman daftar penyewa tetap ringkas.</p>
                </div>
                <a href="penghuni.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Data Penyewa</a>
            </div>

            <?php if ($errorTambah): ?>
                <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errorTambah); ?></div>
            <?php endif; ?>

            <div class="info-blue">
                <i class="fa-solid fa-id-card"></i>
                Lengkapi data diri, upload KTP, lalu pilih kamar dan akun login penyewa
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" placeholder="Masukkan nama lengkap" required>
                    </div>
                    <div class="form-group">
                        <label>Nomor KTP</label>
                        <input type="text" name="nomor_ktp" placeholder="Masukkan nomor KTP" required>
                    </div>

                    <div class="form-group">
                        <label>Upload KTP</label>
                        <input type="file" name="foto_ktp" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label>No. HP (WhatsApp)</label>
                        <input type="text" name="hp" placeholder="Masukkan nomor HP aktif" required>
                    </div>

                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin" required>
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    
                    <!-- KEMBALI DITAMBAHKAN: STATUS SEWA -->
                    <div class="form-group">
                        <label>Status Sewa Awal</label>
                        <select name="status_sewa" required>
                            <option value="Aktif" selected>Aktif (Menghuni Sekarang)</option>
                            <option value="Tidak Aktif">Tidak Aktif (Booking Dulu)</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" rows="3" placeholder="Alamat asal sesuai KTP" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Pekerjaan</label>
                        <input type="text" name="pekerjaan" placeholder="Contoh: Mahasiswa / Karyawan" required>
                    </div>
                    <div class="form-group">
                        <label>Username Login Penyewa</label>
                        <input type="text" name="username" placeholder="Buat username unik" required>
                    </div>

                    <div class="form-group">
                        <label>Pilih Kamar</label>
                        <select name="kamar" required>
                            <option value="">-- Pilih Kamar Tersedia --</option>
                            <?php while ($k = mysqli_fetch_assoc($dataKamarKosong)) { ?>
                                <option value="<?= $k['id_kamar']; ?>">
                                    <?= $k['kode_kamar']; ?> - Rp <?= number_format($k['harga']); ?> / bulan
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Masuk</label>
                        <input type="date" name="tanggal" required value="<?= date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group full-width">
                        <label>Password Login Awal</label>
                        <div class="password-container">
                            <input type="password" name="password" id="password" placeholder="Buat password login" required>
                            <i class="fa-solid fa-eye" id="togglePassword"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" name="tambah" class="btn-submit">
                    <i class="fa-solid fa-save"></i> Simpan Data Penyewa
                </button>
            </form>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        togglePassword.addEventListener('click', function () {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        function toggleSidebar() { 
            document.getElementById("sidebar").classList.toggle("active"); 
            if (window.innerWidth <= 900) { 
                document.getElementById("overlay").classList.toggle("active"); 
            } 
        }
        function closeSidebar() { 
            document.getElementById("sidebar").classList.remove("active"); 
            document.getElementById("overlay").classList.remove("active"); 
        }
    </script>
</body>
</html>