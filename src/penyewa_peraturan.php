<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "tenant_nav.php";

requireRole('penyewa');
ensureAppSchema($conn);

const DENDA_SEPARATOR = '|||';

function parseDendaRule(string $value): array
{
    if (strpos($value, DENDA_SEPARATOR) !== false) {
        [$jenis, $sanksi] = explode(DENDA_SEPARATOR, $value, 2);
        return [trim($jenis), trim($sanksi)];
    }
    if (strpos($value, ':') !== false) {
        [$jenis, $sanksi] = explode(':', $value, 2);
        return [trim($jenis), trim($sanksi)];
    }
    return [trim($value), ''];
}

$ruleGroups = [
    'Kewajiban' => [
        'title' => 'Kewajiban Penghuni',
        'icon' => 'fa-solid fa-check-double',
        'container_class' => 'rule-card',
        'list_tag' => 'ul',
    ],
    'Peraturan Bertamu' => [
        'title' => 'Peraturan Bertamu',
        'icon' => 'fa-solid fa-user-group',
        'container_class' => 'rule-card',
        'list_tag' => 'ul',
    ],
    'Larangan Keras' => [
        'title' => 'Larangan Keras',
        'icon' => 'fa-solid fa-triangle-exclamation',
        'container_class' => 'alert-box',
        'list_tag' => 'ol',
        'description' => 'Penghuni dapat dikeluarkan tanpa pengembalian uang sewa jika terbukti:',
    ],
    'Denda & Sanksi' => [
        'title' => 'Denda & Sanksi Pelanggaran',
        'icon' => 'fa-solid fa-gavel',
        'container_class' => 'rule-card full-width',
        'list_tag' => 'table',
    ],
];

$rulesByCategory = [];
$rulesQuery = mysqli_query($conn, "SELECT id_peraturan, kategori, isi_peraturan FROM peraturan ORDER BY kategori, id_peraturan ASC");
while ($row = mysqli_fetch_assoc($rulesQuery)) {
    $rulesByCategory[$row['kategori'] ?: 'Kewajiban'][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tata Tertib - Budi Homestay</title>
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

        /* RULE CARDS */
        .rule-container { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-top: 24px; }
        .rule-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 12px 28px rgba(18,61,117,0.08); border: none; }
        .full-width { grid-column: span 2; }
        
        /* ALERT BOX (LARANGAN) */
        .alert-box { background: #fffcfc; border-left: 6px solid #eb5757; border-radius: 20px; padding: 25px; grid-column: span 2; box-shadow: 0 12px 28px rgba(18,61,117,0.08); }
        
        h3 { margin-top: 0; color: #0f3c74; font-size: 18px; border-bottom: 2px solid #f0f4f8; padding-bottom: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-box h3 { color: #c0392b !important; }

        .rule-list { padding-left: 20px; line-height: 1.8; color: #444; }
        .rule-list li { margin-bottom: 10px; }

        /* TABLE FOR DENDA */
        .rule-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .rule-table th { text-align: left; background: #f8fbff; padding: 15px; color: #5c6f87; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #edf2f7; }
        .rule-table td { padding: 15px; border-bottom: 1px solid #f0f4f8; font-size: 14px; }

        .empty-note { color: #6e7f95; font-style: italic; font-size: 14px; text-align: center; padding: 20px; }
        .footer-note { margin-top: 40px; text-align: center; color: #6e7f95; font-style: italic; font-size: 13px; }

        @media (max-width: 900px) { .rule-container { grid-template-columns: 1fr; } .full-width, .alert-box { grid-column: span 1; } .main.shift { margin-left: 0; } .header { flex-direction: column; align-items: flex-start; } .header-info-right { text-align: left !important; margin-top: 15px; } }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderTenantSidebar('penyewa_peraturan.php'); ?>

    <div class="main" id="main">
        <!-- HEADER KONSISTEN -->
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1 style="font-size: 24px; margin:0;">Tata Tertib & Peraturan</h1>
                    <p style="margin:5px 0 0; opacity:0.8; font-size: 14px;">Wajib dipatuhi seluruh penghuni demi kenyamanan bersama di Budi Homestay.</p>
                </div>
            </div>
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600;">Akun Penyewa</div>
            </div>
        </div>

        <div class="rule-container">
            <?php foreach ($ruleGroups as $categoryKey => $config): 
                $items = $rulesByCategory[$categoryKey] ?? []; ?>
                
                <div class="<?= $config['container_class']; ?>">
                    <h3><i class="<?= $config['icon']; ?>"></i> <?= $config['title']; ?></h3>
                    
                    <?php if (!empty($config['description'])): ?>
                        <p style="font-size: 14px; margin-top: 0; margin-bottom: 15px; color: #c0392b;"><?= $config['description']; ?></p>
                    <?php endif; ?>

                    <?php if ($config['list_tag'] === 'table'): ?>
                        <?php if (!empty($items)): ?>
                            <div style="overflow-x: auto;">
                                <table class="rule-table">
                                    <thead>
                                        <tr>
                                            <th>Jenis Pelanggaran</th>
                                            <th>Sanksi / Denda</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $rule): 
                                            [$jenis, $sanksi] = parseDendaRule($rule['isi_peraturan']); ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($jenis); ?></strong></td>
                                                <td style="color: #eb5757; font-weight: 700;"><?= htmlspecialchars($sanksi); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="empty-note">Belum ada aturan denda yang ditetapkan.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (!empty($items)): ?>
                            <<?= $config['list_tag']; ?> class="rule-list">
                                <?php foreach ($items as $rule): ?>
                                    <li><?= htmlspecialchars($rule['isi_peraturan']); ?></li>
                                <?php endforeach; ?>
                            </<?= $config['list_tag']; ?>>
                        <?php else: ?>
                            <p class="empty-note">Belum ada aturan di kategori ini.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="footer-note">Peraturan di atas bersifat mengikat dan dapat diperbarui sewaktu-waktu oleh pihak pengelola.</p>
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

    window.addEventListener("resize", syncSidebarLayout);
    syncSidebarLayout();
    </script>
</body>
</html>