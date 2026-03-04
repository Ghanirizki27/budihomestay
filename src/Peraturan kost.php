<?php
session_start();
// Proteksi halaman: Jika belum login, tendang ke login.php
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

$halaman = 'peraturan.php'; // Untuk menandai menu aktif
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peraturan Kost - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Menggunakan base CSS yang kamu berikan sebelumnya */
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f9ff;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #0f2f59, #123d75);
            position: fixed;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 5px 0 25px rgba(0,0,0,0.08);
        }

        .sidebar h2 {
            text-align: center;
            padding: 20px 10px;
            margin: 0;
            font-size: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: #dbe9ff;
            text-decoration: none;
            transition: 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.1);
            padding-left: 28px;
            color: white;
        }

        .main {
            margin-left: 250px;
            padding: 35px;
        }

        .header {
            background: linear-gradient(90deg, #4da6ff, #2f80ed);
            color: white;
            padding: 30px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 10px 20px rgba(47,128,237,0.2);
            margin-bottom: 30px;
        }

        /* Styling Konten Peraturan */
        .rule-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .rule-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .rule-card h3 {
            margin-top: 0;
            color: #123d75;
            border-bottom: 2px solid #4da6ff;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rule-card ul {
            padding-left: 20px;
            line-height: 1.8;
            color: #444;
        }

        .rule-card ul li {
            margin-bottom: 10px;
        }

        .alert-box {
            grid-column: span 2;
            background: #fff5f5;
            border-left: 5px solid #ff4d4d;
            padding: 20px;
            border-radius: 10px;
            color: #c0392b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 15px;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            border: 1px solid #eee;
            text-align: left;
        }

        th {
            background-color: #f8fbff;
            color: #123d75;
        }

        .footer-note {
            margin-top: 30px;
            text-align: center;
            color: #888;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="menu-atas">
        <h2><i class="fa-solid fa-house"></i> Budi Homestay</h2>
        <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="kamar.php"><i class="fa-solid fa-bed"></i> Data Kamar</a>
        <a href="penghuni.php"><i class="fa-solid fa-users"></i> Data Penghuni</a>
         <a href="pembayaran.php" class="<?= ($halaman == 'pembayaran.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-money-bill-wave"></i> Pembayaran
        </a>

        <a href="laporan.php" class="<?= ($halaman == 'laporan.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-column"></i> Laporan
        </a>
        <a href="peraturan.php" class="active"><i class="fa-solid fa-circle-exclamation"></i> Peraturan Kos</a>
    </div>
    <div class="menu-bawah">
        <a href="logout.php" style="background: #0d2c54;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<div class="main">
    <div class="header">
        <i class="fa-solid fa-clipboard-list" style="font-size: 40px;"></i>
        <div>
            <h1 style="margin:0;">Tata Tertib & Peraturan</h1>
            <p style="margin:0;">Harap dipatuhi demi kenyamanan bersama di Budi Homestay</p>
        </div>
    </div>

    <div class="rule-container">
        <div class="rule-card">
            <h3><i class="fa-solid fa-check-double"></i> Kewajiban Penghuni</h3>
            <ul>
                <li>Menyerahkan fotokopi KTP/Identitas diri yang sah saat mendaftar.</li>
                <li>Membayar uang sewa kost paling lambat tanggal 5 setiap bulannya.</li>
                <li>Menjaga kebersihan kamar masing-masing dan area bersama.</li>
                <li>Mematikan lampu dan alat elektronik lainnya saat meninggalkan kamar.</li>
                <li>Mengunci pintu pagar demi keamanan bersama setelah keluar/masuk.</li>
                <li>Waktu Malam bagi penghuni adalah pukul 22.00 WIB.
                <li>menjaga ketenangan lingkungan kost dengan tidak membuat kegaduhan terutama di malam hari.</li>
                </li>
            </ul>
        </div>

        <div class="rule-card">
            <h3><i class="fa-solid fa-user-group"></i> Peraturan Bertamu</h3>
            <ul>
                <li>Jam bertamu maksimal hingga pukul 22.00 WIB.</li>
                <li>Tamu lawan jenis dilarang masuk ke dalam kamar (harus di ruang tamu/area terbuka).</li>
                <li>Tamu yang menginap wajib melapor kepada pengelola.</li>
                <li>Penghuni bertanggung jawab penuh atas perilaku tamu yang dibawa.
                <li>Dilarang membuat kegaduhan yang mengganggu ketenangan warga sekitar saat bertamu.</li>
                </li>
            </ul>
        </div>

        <div class="alert-box">
            <h3><i class="fa-solid fa-triangle-exclamation"></i> Larangan Keras (Sanksi Drop Out)</h3>
            <p>Penghuni akan dikeluarkan secara tidak hormat tanpa pengembalian uang sewa jika terbukti:</p>
            <ol>
                <li>Membawa, menggunakan, atau mengedarkan Narkoba dan Miras.</li>
                <li>Melakukan tindakan asusila atau perjudian di lingkungan kost.</li>
                <li>Membawa senjata tajam atau barang berbahaya lainnya.</li>
                <li>Membuat kegaduhan yang mengganggu ketenangan warga sekitar.</li>
                <li>Merusak fasilitas kost secara sengaja.</li>
            </ol>
        </div>

        <div class="rule-card" style="grid-column: span 2;">
            <h3><i class="fa-solid fa-gavel"></i> Denda & Sanksi Pelanggaran</h3>
            <table>
                <thead>
                    <tr>
                        <th>Jenis Pelanggaran</th>
                        <th>Sanksi / Denda</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Keterlambatan Pembayaran (> tanggal 5)</td>
                        <td>Peringatan!</td>
                    </tr>
                    <tr>
                        <td>Menghilangkan Kunci Kamar</td>
                        <td>Mengganti kunci kamar yang hilang</td>
                    </tr>
                    <tr>
                        <td>Merusak fasilitas kost (lemari, kasur, tembok)</td>
                        <td>Ganti rugi sesuai biaya perbaikan</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <p class="footer-note">Peraturan ini dapat berubah sewaktu-waktu sesuai kebijakan pengelola Budi Homestay.</p>
</div>

</body>
</html>