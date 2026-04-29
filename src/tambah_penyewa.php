<?php
session_start();
include_once "auth.php";
requireRole('admin');
include "koneksi.php";
include_once "db_migrations.php";

ensureAppSchema($conn);

$errorTambah = null;

if (isset($_POST['tambah'])) {
    $nama    = mysqli_real_escape_string($conn, $_POST['nama']);
    $ktp     = mysqli_real_escape_string($conn, $_POST['nomor_ktp']);
    $hp      = mysqli_real_escape_string($conn, $_POST['hp']);
    $gender  = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $alamat  = mysqli_real_escape_string($conn, $_POST['alamat']);
    $kerja   = mysqli_real_escape_string($conn, $_POST['pekerjaan']);
    $kamar   = mysqli_real_escape_string($conn, $_POST['kamar']);
    $tgl     = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $passwordInput = $_POST['password'];
    $fotoKtp = '';

    $cekUsername = mysqli_query($conn, "SELECT id_admin FROM admin WHERE username='$username' LIMIT 1");
    if (mysqli_num_rows($cekUsername) > 0) {
        $errorTambah = 'Username penyewa sudah dipakai. Gunakan username lain.';
    }

    if (!$errorTambah && !empty($_FILES['foto_ktp']['name']) && is_uploaded_file($_FILES['foto_ktp']['tmp_name'])) {
        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ktp';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = strtolower(pathinfo($_FILES['foto_ktp']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (in_array($extension, $allowedExtensions, true)) {
            $fileName = 'ktp_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
            $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

            if (move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $targetPath)) {
                $fotoKtp = 'uploads/ktp/' . $fileName;
            }
        }
    }

    if (!$errorTambah) {
        mysqli_query($conn, "
            INSERT INTO penyewa (
                nama, nomor_ktp, foto_ktp, nomor_telepon, jenis_kelamin, alamat,
                pekerjaan, tanggal_masuk, id_kamar, status_sewa
            )
            VALUES (
                '$nama', '$ktp', '$fotoKtp', '$hp', '$gender', '$alamat',
                '$kerja', '$tgl', '$kamar', 'Aktif'
            )
        ");

        $idPenyewa = mysqli_insert_id($conn);
        $passwordHash = md5($passwordInput);
        $namaLengkap = mysqli_real_escape_string($conn, $_POST['nama']);

        mysqli_query($conn, "
            INSERT INTO admin (username, password, nama_lengkap, role, id_penyewa)
            VALUES ('$username', '$passwordHash', '$namaLengkap', 'penyewa', '$idPenyewa')
        ");

        mysqli_query($conn, "
            UPDATE kamar SET status='Ditempati'
            WHERE id_kamar='$kamar'
        ");

        $_SESSION['flash_penyewa'] = 'Data penyewa baru dan akun login penyewa berhasil ditambahkan.';
        header("Location: penghuni.php");
        exit;
    }
}

$dataKamarKosong = mysqli_query($conn, "
    SELECT * FROM kamar WHERE status='Kosong'
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
    <title>Tambah Penyewa</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #eaf3ff, #f8fbff);
}

.main {
    max-width: 1080px;
    margin: 0 auto;
    padding: 35px;
}

.header {
    background: linear-gradient(90deg, #4da6ff, #2f80ed);
    color: white;
    padding: 20px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-title h2,
.header-title p {
    margin: 0;
}

.header-title p {
    margin-top: 4px;
    opacity: 0.9;
}

.card {
    margin-top: 30px;
    padding: 25px;
    border-radius: 18px;
    background: white;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}

.card-head h3,
.card-head p {
    margin: 0;
}

.card-head p {
    margin-top: 6px;
    color: #567;
}

.back-link {
    text-decoration: none;
    color: #2f80ed;
    font-weight: 700;
}

input, select, textarea {
    padding: 12px 14px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-family: inherit;
}

textarea {
    resize: vertical;
    min-height: 96px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field-group.full {
    grid-column: 1 / -1;
}

.field-group label {
    font-weight: 600;
    color: #123d75;
}

.step-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #2f80ed;
    background: #eef5ff;
    border-radius: 999px;
    padding: 8px 14px;
    margin-bottom: 18px;
}

.button-primary {
    margin-top: 18px;
    padding: 12px 18px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(90deg, #4da6ff, #2f80ed);
    color: white;
    font-weight: 700;
    cursor: pointer;
}

.alert-error {
    margin-top: 20px;
    padding: 14px 18px;
    border-radius: 12px;
    background: #ffe9e9;
    color: #b53a3a;
    font-weight: 600;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .card-head {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
</head>
<body>

<div class="main" id="main">
    <div class="header">
        <div style="display:flex; align-items:center; gap:14px;">
            <div class="header-title">
                <h2>Tambah Penyewa</h2>
                <p>Lengkapi identitas calon penyewa, kamar, dan akun login penyewa</p>
            </div>
        </div>
        <a class="back-link" href="penghuni.php">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if ($errorTambah): ?>
        <div class="alert-error"><?= htmlspecialchars($errorTambah); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Form Pendaftaran Penyewa</h3>
                <p>Data dibuat terpisah agar halaman daftar penyewa tetap ringkas.</p>
            </div>
            <a class="back-link" href="penghuni.php">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Data Penyewa
            </a>
        </div>

        <div class="step-badge">
            <i class="fa-solid fa-id-card"></i>
            Lengkapi data diri, upload KTP, lalu pilih kamar dan akun login penyewa
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" placeholder="Masukkan nama lengkap" required>
                </div>
                <div class="field-group">
                    <label>Nomor KTP</label>
                    <input type="text" name="nomor_ktp" placeholder="Masukkan nomor KTP" required>
                </div>
                <div class="field-group">
                    <label>Upload KTP</label>
                    <input type="file" name="foto_ktp" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                </div>
                <div class="field-group">
                    <label>No. HP</label>
                    <input type="text" name="hp" placeholder="Masukkan nomor HP aktif" required>
                </div>
                <div class="field-group">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin" required>
                        <option value="">Pilih jenis kelamin</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="field-group full">
                    <label>Alamat</label>
                    <textarea name="alamat" placeholder="Masukkan alamat lengkap sesuai KTP" required></textarea>
                </div>
                <div class="field-group">
                    <label>Pekerjaan</label>
                    <input type="text" name="pekerjaan" placeholder="Contoh: Mahasiswa / Karyawan" required>
                </div>
                <div class="field-group">
                    <label>Username Login Penyewa</label>
                    <input type="text" name="username" placeholder="Contoh: andri02" required>
                </div>
                <div class="field-group">
                    <label>Pilih Kamar</label>
                    <select name="kamar" required>
                        <option value="">Pilih kamar yang tersedia</option>
                        <?php while ($k = mysqli_fetch_assoc($dataKamarKosong)) { ?>
                            <option value="<?= $k['id_kamar']; ?>">
                                <?= $k['nomor_kamar']; ?> - Rp <?= number_format($k['harga']); ?> / bulan
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="field-group">
                    <label>Tanggal Masuk</label>
                    <input type="date" name="tanggal" required>
                </div>
                <div class="field-group">
                    <label>Password Login Penyewa</label>
                    <input type="text" name="password" placeholder="Buat password awal" required>
                </div>
            </div>

            <button class="button-primary" name="tambah">Simpan Data Penyewa</button>
        </form>
    </div>
</div>

</body>
</html>
