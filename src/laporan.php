<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "admin_nav.php";

requireRole('admin');
ensureAppSchema($conn);

// --- 1. PROSES SIMPAN PENGELUARAN ---
if (isset($_POST['tambah_pengeluaran'])) {
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal_pengeluaran']);
    $jumlah = (int) $_POST['jumlah_pengeluaran'];
    $keterangan = mysqli_real_escape_string($conn, trim($_POST['keterangan_pengeluaran']));

    mysqli_query($conn, "
        INSERT INTO transaksi_keuangan (tanggal, jenis, jumlah, keterangan)
        VALUES ('$tanggal', 'Pengeluaran', '$jumlah', '$keterangan')
    ");
    $success_msg = "Pengeluaran berhasil dicatat!";
}

// --- 2. AMBIL TOTAL DATA ---
$total_pemasukan = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM pembayaran WHERE status = 'Lunas'"))['total'];
$total_pengeluaran = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM transaksi_keuangan WHERE jenis = 'Pengeluaran'"))['total'];
$saldo_akhir = $total_pemasukan - $total_pengeluaran;

// --- 3. DATA GRAFIK ---
$bulan_labels = [1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "Mei", 6 => "Jun", 7 => "Jul", 8 => "Agu", 9 => "Sep", 10 => "Okt", 11 => "Nov", 12 => "Des"];
$pemasukanPerBulan = array_fill(1, 12, 0);
$pengeluaranPerBulan = array_fill(1, 12, 0);

$chartIn = mysqli_query($conn, "SELECT MONTH(tanggal_bayar) AS bulan, SUM(jumlah) AS total FROM pembayaran WHERE status = 'Lunas' AND YEAR(tanggal_bayar) = YEAR(CURDATE()) GROUP BY MONTH(tanggal_bayar)");
while ($row = mysqli_fetch_assoc($chartIn)) { $pemasukanPerBulan[(int)$row['bulan']] = (int)$row['total']; }

$chartOut = mysqli_query($conn, "SELECT MONTH(tanggal) AS bulan, SUM(jumlah) AS total FROM transaksi_keuangan WHERE jenis = 'Pengeluaran' AND YEAR(tanggal) = YEAR(CURDATE()) GROUP BY MONTH(tanggal)");
while ($row = mysqli_fetch_assoc($chartOut)) { $pengeluaranPerBulan[(int)$row['bulan']] = (int)$row['total']; }

// --- 4. RIWAYAT GABUNGAN ---
$riwayat = mysqli_query($conn, "
    SELECT tanggal_bayar AS tgl, 'Pemasukan' AS tipe, jumlah, 'Pembayaran Sewa' AS ket FROM pembayaran WHERE status = 'Lunas'
    UNION ALL
    SELECT tanggal AS tgl, jenis AS tipe, jumlah, keterangan AS ket FROM transaksi_keuangan WHERE jenis = 'Pengeluaran'
    ORDER BY tgl DESC LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* HEADER KONSISTEN */
        .header { background: linear-gradient(120deg, #1f4f8f, #2f80ed); color: white; border-radius: 24px; padding: 28px; display: flex; align-items: center; justify-content: space-between; gap: 20px; box-shadow: 0 18px 35px rgba(47,128,237,0.25); }
        .menu-icon { cursor: pointer; padding: 10px; border-radius: 10px; font-size: 20px; background: rgba(255,255,255,0.15); display: flex; }

        /* CARDS & METRICS */
        .grid-metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 22px; margin-top: 24px; }
        .card { background: white; border-radius: 20px; padding: 24px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); border: none; }
        .metric { font-size: 28px; font-weight: 800; color: #0f3c74; margin-top: 10px; }
        
        /* INPUT PENGELUARAN (POSISI BARU: HORIZONTAL CARD) */
        .input-card { margin-top: 24px; background: white; border-radius: 20px; padding: 25px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); border-top: 5px solid #2f80ed; }
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; font-size: 13px; font-weight: 700; color: #5c6f87; margin-bottom: 8px; }
        .input-with-icon { position: relative; }
        .input-with-icon i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #2f80ed; }
        input, textarea { width: 100%; padding: 12px 12px 12px 40px; border-radius: 12px; border: 1px solid #ccd7e5; box-sizing: border-box; font-family: inherit; transition: 0.3s; }
        textarea { padding-left: 15px; min-height: 48px; height: 48px; }
        
        .btn-simpan { padding: 12px 25px; height: 48px; border: none; border-radius: 12px; background: linear-gradient(90deg, #4da6ff, #2f80ed); color: white; font-weight: 700; cursor: pointer; transition: 0.3s; box-shadow: 0 8px 15px rgba(47,128,237,0.2); }
        .btn-simpan:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(47,128,237,0.3); }

        /* TABLES */
        .table-container { overflow-x: auto; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { text-align: left; background: #f8fbff; padding: 15px; color: #5c6f87; font-size: 13px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f0f4f8; font-size: 14px; }
        .txt-pemasukan { color: #27ae60; font-weight: bold; }
        .txt-pengeluaran { color: #eb5757; font-weight: bold; }
        
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; background: #e8f8ee; color: #1c854a; font-weight: 600; border-left: 5px solid #27ae60; margin-top: 24px; }

        @media (max-width: 900px) { .main { padding: 20px; } .main.shift { margin-left: 0; } .header { flex-direction: column; align-items: flex-start; } .header-info-right { text-align: left !important; margin-top: 15px; } .form-group { flex: 1 1 100%; } }
    </style>
</head>
<body>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderAdminSidebar('laporan.php'); ?>

    <div class="main" id="main">
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1 style="font-size: 24px; margin:0;">Laporan Keuangan</h1>
                    <p style="margin:5px 0 0; opacity: 0.8; font-size: 14px;">Monitor arus kas dan catat biaya operasional homestay.</p>
                </div>
            </div>
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600;">Level: Administrator</div>
            </div>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert"><i class="fa-solid fa-circle-check"></i> <?= $success_msg; ?></div>
        <?php endif; ?>

        <!-- METRICS -->
        <div class="grid-metrics">
            <div class="card">
                <h3 style="margin:0; color:#666; font-size:12px; text-transform:uppercase; letter-spacing:1px;">Pemasukan (Sewa)</h3>
                <div class="metric" style="color: #27ae60;">Rp <?= number_format($total_pemasukan); ?></div>
            </div>
            <div class="card">
                <h3 style="margin:0; color:#666; font-size:12px; text-transform:uppercase; letter-spacing:1px;">Total Pengeluaran</h3>
                <div class="metric" style="color: #eb5757;">Rp <?= number_format($total_pengeluaran); ?></div>
            </div>
            <div class="card">
                <h3 style="margin:0; color:#666; font-size:12px; text-transform:uppercase; letter-spacing:1px;">Saldo Akhir</h3>
                <div class="metric">Rp <?= number_format($saldo_akhir); ?></div>
            </div>
        </div>

        <!-- GRAFIK FULL WIDTH -->
        <div class="card" style="margin-top: 24px;">
            <h3 style="margin-top:0; color:#0f3c74;"><i class="fa-solid fa-chart-area"></i> Grafik Keuangan <?= date('Y'); ?></h3>
            <canvas id="financeChart" style="max-height: 350px; width: 100%;"></canvas>
        </div>

        <!-- INPUT PENGELUARAN (POSISI PINDAH KE BAWAH GRAFIK) -->
        <div class="input-card">
            <h3 style="margin-top:0; color:#0f3c74; margin-bottom:20px;"><i class="fa-solid fa-plus-circle"></i> Catat Pengeluaran Baru</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal</label>
                        <div class="input-with-icon">
                            <i class="fa-solid fa-calendar-alt"></i>
                            <input type="date" name="tanggal_pengeluaran" required value="<?= date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nominal (Rp)</label>
                        <div class="input-with-icon">
                            <i class="fa-solid fa-money-bill-wave"></i>
                            <input type="number" name="jumlah_pengeluaran" placeholder="Misal: 50000" required>
                        </div>
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label>Keterangan / Keperluan</label>
                        <textarea name="keterangan_pengeluaran" placeholder="Misal: Perbaikan lampu lorong" required></textarea>
                    </div>
                    <button type="submit" name="tambah_pengeluaran" class="btn-simpan">
                        <i class="fa-solid fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>

        <!-- TABEL RIWAYAT -->
        <div class="card" style="margin-top:24px;">
            <h3 style="margin-top:0; color:#0f3c74;"><i class="fa-solid fa-history"></i> Riwayat Transaksi</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Keterangan</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($riwayat)): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($row['tgl'])); ?></td>
                            <td><span class="<?= $row['tipe'] == 'Pemasukan' ? 'txt-pemasukan' : 'txt-pengeluaran'; ?>"><?= $row['tipe']; ?></span></td>
                            <td><?= htmlspecialchars($row['ket']); ?></td>
                            <td class="<?= $row['tipe'] == 'Pemasukan' ? 'txt-pemasukan' : 'txt-pengeluaran'; ?>">
                                <?= $row['tipe'] == 'Pemasukan' ? '+' : '-'; ?> Rp <?= number_format($row['jumlah']); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const main = document.getElementById("main");

    function syncSidebarLayout(){ if (window.innerWidth > 900 && sidebar.classList.contains("active")) { main.classList.add("shift"); return; } main.classList.remove("shift"); }
    function toggleSidebar(){ sidebar.classList.toggle("active"); if (window.innerWidth <= 900) { overlay.classList.toggle("active"); } syncSidebarLayout(); }
    function closeSidebar(){ sidebar.classList.remove("active"); overlay.classList.remove("active"); syncSidebarLayout(); }

    function updateDateTime() {
        const skrg = new Date();
        document.getElementById("tanggal").textContent = skrg.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        document.getElementById("waktu").textContent = skrg.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setInterval(updateDateTime, 1000); updateDateTime();

    new Chart(document.getElementById('financeChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_values($bulan_labels)); ?>,
            datasets: [
                { label: 'Pemasukan', data: <?= json_encode(array_values($pemasukanPerBulan)); ?>, borderColor: '#27ae60', backgroundColor: 'rgba(39, 174, 96, 0.1)', tension: 0.4, fill: true },
                { label: 'Pengeluaran', data: <?= json_encode(array_values($pengeluaranPerBulan)); ?>, borderColor: '#eb5757', backgroundColor: 'rgba(235, 87, 87, 0.1)', tension: 0.4, fill: true }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
    });

    window.addEventListener("resize", syncSidebarLayout);
    syncSidebarLayout();
    </script>
</body>
</html>