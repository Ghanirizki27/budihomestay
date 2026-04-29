<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "admin_nav.php";

requireRole('admin');
ensureAppSchema($conn);

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
    <title>Pengumuman - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f8fbff; overflow-x: hidden; }
        .sidebar {
            width: 250px; height: 100vh;
            background: linear-gradient(180deg, #0f2f59, #123d75);
            position: fixed; left: -250px; top: 0;
            transition: 0.3s; z-index: 1000;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .sidebar.active { left: 0; }
        .sidebar h2 { color: white; text-align: center; padding: 20px; margin: 0; }
        .sidebar a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #dbe9ff; text-decoration: none; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); padding-left: 28px; }
        .sidebar a.active { background: rgba(255,255,255,0.14); color: white; margin: 6px 12px; border-radius: 14px; }
        .menu-bawah a { background: #0d2c54; }

        .main { padding: 35px; transition: 0.3s; margin-left: 0; }
        .main.shift { margin-left: 250px; }

        .header {
            background: linear-gradient(90deg, #4da6ff, #2f80ed);
            color: white; padding: 15px 25px;
            border-radius: 18px; display: flex; align-items: center;
            box-shadow: 0 10px 25px rgba(47,128,237,0.2); margin-bottom: 30px;
        }
        .menu-icon { font-size: 20px; cursor: pointer; padding: 10px; border-radius: 8px; transition: 0.3s; }
        .menu-icon:hover { background: rgba(255,255,255,0.2); }

        .card { background: white; padding: 30px; border-radius: 18px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin-bottom: 25px; }
        input, textarea { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; font-family: inherit; }
        button { background: #2f80ed; color: white; border: none; padding: 12px 25px; border-radius: 10px; cursor: pointer; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; background: #f1f7ff; padding: 15px; color: #2f80ed; }
        td { padding: 15px; border-bottom: 1px solid #eee; }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; background: #d4edda; color: #155724; font-weight: bold; }

        @media (max-width: 765px) {
            .main.shift { margin-left: 0; }
            .main { padding: 20px; }
        }
    </style>
</head>
<body>

<?php renderAdminSidebar('pengumuman.php'); ?>

<div class="main" id="main">
    <div class="header">
        <div class="menu-icon" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </div>
        <h2 style="margin: 0 0 0 20px;">Pengumuman</h2>
    </div>

    <?php if(isset($_GET['pesan']) && $_GET['pesan'] == "berhasil"): ?>
        <div class="alert">Pengumuman Berhasil Dikirim!</div>
    <?php endif; ?>

    <div class="card">
        <h3><i class="fa-solid fa-pen-to-square"></i> Buat Pengumuman</h3>
        <form method="POST">
            <input type="text" name="judul" placeholder="Judul Pengumuman" required>
            <textarea name="isi" rows="4" placeholder="Tulis isi pengumuman di sini..." required></textarea>
            <button type="submit" name="tambah">Simpan & Kirim</button>
        </form>
    </div>

    <div class="card">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Riwayat</h3>
        <table>
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Isi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($data_pengumuman)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['judul']); ?></strong></td>
                    <td><?= htmlspecialchars(mb_strimwidth($row['isi'], 0, 50, '...')); ?></td>
                    <td>
                        <a href="pengumuman.php?hapus=<?= (int) $row['id_pengumuman']; ?>"
                           style="color: red; text-decoration: none;"
                           onclick="return confirm('Hapus pengumuman?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const main = document.getElementById("main");

function syncSidebarLayout() {
    if (window.innerWidth > 765 && sidebar.classList.contains("active")) {
        main.classList.add("shift");
        overlay.classList.remove("active");
        return;
    }

    main.classList.remove("shift");
}

function toggleSidebar() {
    sidebar.classList.toggle("active");

    if (window.innerWidth <= 765) {
        overlay.classList.toggle("active");
    } else {
        overlay.classList.remove("active");
    }

    syncSidebarLayout();
}

function closeSidebar() {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
    syncSidebarLayout();
}

window.addEventListener("resize", syncSidebarLayout);
syncSidebarLayout();
</script>

</body>
</html>
