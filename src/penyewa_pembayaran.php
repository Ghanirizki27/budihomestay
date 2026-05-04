<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "tenant_nav.php";
include_once "tenant_context.php";

requireRole('penyewa');
ensureAppSchema($conn);

$penyewa = getLoggedInTenant($conn);
$id_penyewa = (int) ($penyewa['id_penyewa'] ?? 0);

// --- PROSES UPLOAD BUKTI PEMBAYARAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti'])) {
    $metode = mysqli_real_escape_string($conn, $_POST['metode']);
    $jumlah = (int) ($penyewa['harga'] ?? 0);
    $jatuh_tempo = mysqli_real_escape_string($conn, $_POST['jatuh_tempo']);
    
    $target_dir = "uploads/bukti/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $file_extension = pathinfo($_FILES["bukti"]["name"], PATHINFO_EXTENSION);
    $file_name = "BUKTI_" . $id_penyewa . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["bukti"]["tmp_name"], $target_file)) {
        $query = "INSERT INTO pembayaran (id_penyewa, tanggal_bayar, jatuh_tempo, jumlah, metode_pembayaran, bukti_pembayaran, status) 
                  VALUES ('$id_penyewa', NOW(), '$jatuh_tempo', '$jumlah', '$metode', '$file_name', 'Menunggu Validasi')";
        
        if (mysqli_query($conn, $query)) {
            header("Location: penyewa_pembayaran.php?status=success");
            exit;
        }
    }
}

$summary = getTenantPaymentSummary($conn, $id_penyewa, $penyewa['tanggal_masuk'] ?? null);
$riwayat = mysqli_query($conn, "SELECT * FROM pembayaran WHERE id_penyewa = '$id_penyewa' ORDER BY tanggal_bayar DESC");

// --- AMBIL DATA METODE PEMBAYARAN DARI DATABASE ---
$q_metode = mysqli_query($conn, "SELECT * FROM metode_pembayaran ORDER BY id_metode ASC");

// --- LOGIKA TAGIHAN PINTAR ---
$bulan_ini = date('m');
$tahun_ini = date('Y');
$cek_lunas = mysqli_query($conn, "SELECT SUM(jumlah) as total_masuk FROM pembayaran 
                                  WHERE id_penyewa = '$id_penyewa' 
                                  AND status = 'Lunas' 
                                  AND MONTH(tanggal_bayar) = '$bulan_ini' 
                                  AND YEAR(tanggal_bayar) = '$tahun_ini'");
$data_lunas = mysqli_fetch_assoc($cek_lunas);
$sudah_bayar = (int)($data_lunas['total_masuk'] ?? 0);
$harga_sewa = (int)($penyewa['harga'] ?? 0);

// Hitung sisa tagihan
$sisa_tagihan = $harga_sewa - $sudah_bayar;
if($sisa_tagihan < 0) $sisa_tagihan = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Budi Homestay</title>
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
        
        /* HEADER KONSISTEN */
        .header { background: linear-gradient(120deg, #1f4f8f, #2f80ed); color: white; border-radius: 24px; padding: 28px; display: flex; align-items: center; justify-content: space-between; gap: 20px; box-shadow: 0 18px 35px rgba(47,128,237,0.25); }
        .menu-icon { cursor: pointer; padding: 10px; border-radius: 10px; font-size: 20px; background: rgba(255,255,255,0.15); display: flex; align-items: center; }

        /* GRID & CARDS */
        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; margin-top: 24px; }
        .card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); border: none; }
        .card h3 { margin: 0; color: #0f3c74; font-size: 18px; border-bottom: 2px solid #f0f4f8; padding-bottom: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        .metric-label { color: #637892; font-size: 13px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; }
        .metric-value { font-size: 30px; font-weight: 800; }
        
        /* FORM */
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 8px; font-weight: 700; color: #5c6f87; font-size: 14px; }
        select, input[type="file"] { width: 100%; padding: 12px; border: 1px solid #ccd7e5; border-radius: 12px; box-sizing: border-box; font-family: inherit; transition: 0.3s; }
        select:focus { border-color: #2f80ed; outline: none; box-shadow: 0 0 0 4px rgba(47,128,237,0.1); }
        
        .btn-kirim { width: 100%; padding: 14px; border: none; border-radius: 12px; background: linear-gradient(90deg, #4da6ff, #2f80ed); color: white; font-weight: 700; cursor: pointer; transition: 0.3s; box-shadow: 0 8px 15px rgba(47,128,237,0.2); font-size: 15px; }
        .btn-kirim:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(47,128,237,0.3); }

        /* DYNAMIC PAYMENT INFO */
        .payment-info { background: #f0f7ff; border: 1px dashed #2f80ed; padding: 15px; border-radius: 12px; margin-bottom: 18px; display: none; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* TABLE */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f8fbff; padding: 15px; color: #5c6f87; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #edf2f7; }
        td { padding: 15px; border-bottom: 1px solid #f0f4f8; font-size: 14px; }
        
        .status-chip { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; display: inline-block; }
        .status-pending { background: #fff4db; color: #a86c00; }
        .status-lunas { background: #e8f8ee; color: #1c854a; }

        .alert-success { padding: 15px 20px; border-radius: 12px; background: #e8f8ee; color: #1c854a; font-weight: 600; border-left: 5px solid #27ae60; margin-top: 24px; }

        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } .main.shift { margin-left: 0; } .header { flex-direction: column; align-items: flex-start; } .header-info-right { text-align: left !important; margin-top: 15px; } }
    </style>
</head>
<body>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderTenantSidebar('penyewa_pembayaran.php'); ?>

    <div class="main" id="main">
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1 style="font-size: 24px; margin:0;">Pembayaran Sewa</h1>
                    <p style="margin:5px 0 0; opacity:0.8; font-size: 14px;">Halo, <?= htmlspecialchars($_SESSION['nama']); ?>! Kelola tagihan bulanan Anda di sini.</p>
                </div>
            </div>
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600;">Akun Penyewa</div>
            </div>
        </div>

        <?php if(isset($_GET['status']) && $_GET['status'] == "success"): ?>
            <div class="alert-success"><i class="fa-solid fa-circle-check"></i> Bukti pembayaran berhasil dikirim! Mohon tunggu validasi admin.</div>
        <?php endif; ?>

        <div class="grid">
            <!-- INFO TAGIHAN PINTAR -->
            <div class="card">
                <div class="metric-label">Tagihan Bulan Ini</div>
                
                <?php if ($sisa_tagihan <= 0): ?>
                    <!-- Tampilan Jika Lunas -->
                    <div class="metric-value" style="color: #27ae60;">LUNAS</div>
                    <p style="font-size: 13px; color: #27ae60; margin-top: 5px;">Terima kasih! Pembayaran bulan ini sudah kami terima.</p>
                <?php else: ?>
                    <!-- Tampilan Jika Belum Lunas -->
                    <div class="metric-value" style="color: #eb5757;">Rp <?= number_format($sisa_tagihan); ?></div>
                    <p style="font-size: 13px; color: #637892; margin-top: 5px;">Silakan selesaikan sisa tagihan Anda.</p>
                <?php endif; ?>

                <div style="margin-top:25px; padding-top:15px; border-top: 1px solid #f0f4f8;">
                    <p style="color:#637892; margin:0; font-size: 12px; font-weight: 700;">JATUH TEMPO SELANJUTNYA:</p>
                    <strong style="color: #eb5757; font-size: 18px;"><?= $summary['jatuh_tempo'] ?? '-'; ?></strong>
                </div>
                <div style="margin-top: 20px; color: #5c6f87; font-size: 13px; line-height: 1.5;">
                    <i class="fa-solid fa-circle-info" style="color: #2f80ed;"></i> Pembayaran dilakukan setiap bulan sesuai tanggal masuk Anda.
                </div>
            </div>

            <!-- FORM KONFIRMASI -->
            <div class="card">
                <h3><i class="fa-solid fa-file-invoice-dollar" style="color: #2f80ed;"></i> Konfirmasi Pembayaran</h3>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="jatuh_tempo" value="<?= $summary['jatuh_tempo']; ?>">
                    
                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select name="metode" id="metode_pembayaran" required onchange="showPaymentDetail()">
                            <option value="">-- Pilih Metode --</option>
                            <?php while ($m = mysqli_fetch_assoc($q_metode)): ?>
                                <option value="<?= htmlspecialchars($m['nama_metode']); ?>" 
                                        data-nomor="<?= htmlspecialchars($m['nomor_tujuan']); ?>" 
                                        data-an="<?= htmlspecialchars($m['atas_nama']); ?>">
                                    <?= htmlspecialchars($m['nama_metode']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div id="dynamic_payment_info" class="payment-info">
                        <p style="margin:0; font-size:12px; color:#5c6f87;">Silakan transfer ke rekening/nomor berikut:</p>
                        <p id="info_nomor" style="font-size: 18px; color: #123d75; font-weight: 800; margin: 8px 0;"></p>
                        <p id="info_an" style="margin:0; font-weight: 700; color: #2f80ed;"></p>
                    </div>

                    <div class="form-group">
                        <label>Upload Bukti Transfer (JPG/PNG)</label>
                        <input type="file" name="bukti" accept="image/*" required>
                    </div>

                    <button type="submit" class="btn-kirim">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Konfirmasi Pembayaran
                    </button>
                </form>
            </div>
        </div>

        <!-- RIWAYAT -->
        <div class="card" style="margin-top: 24px;">
            <h3><i class="fa-solid fa-history"></i> Riwayat Pembayaran Anda</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tgl Bayar</th>
                            <th>Metode</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($riwayat) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($riwayat)): 
                                $status_class = ($row['status'] == 'Lunas') ? 'status-lunas' : 'status-pending';
                            ?>
                            <tr>
                                <td><strong><?= date('d M Y', strtotime($row['tanggal_bayar'])); ?></strong></td>
                                <td><?= !empty($row['metode_pembayaran']) ? htmlspecialchars($row['metode_pembayaran']) : '<span style="color:#bbb;">Manual</span>'; ?></td>
                                <td style="color:#2f80ed; font-weight:bold;">Rp <?= number_format($row['jumlah']); ?></td>
                                <td><span class="status-chip <?= $status_class; ?>"><?= $row['status']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #999; padding: 30px;">Belum ada riwayat pembayaran yang tercatat.</td></tr>
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

    function showPaymentDetail() {
        const select = document.getElementById('metode_pembayaran');
        const infoBox = document.getElementById('dynamic_payment_info');
        if (select.value !== "") {
            const selectedOption = select.options[select.selectedIndex];
            const nomor = selectedOption.getAttribute('data-nomor');
            const an = selectedOption.getAttribute('data-an');
            document.getElementById('info_nomor').textContent = nomor;
            document.getElementById('info_an').textContent = "A/N: " + an;
            infoBox.style.display = 'block';
        } else {
            infoBox.style.display = 'none';
        }
    }
    window.addEventListener("resize", syncSidebarLayout);
    syncSidebarLayout();
    </script>
</body>
</html>