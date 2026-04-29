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
        'list_tag' => 'ul',
    ],
];

$rulesByCategory = [];
$rulesQuery = mysqli_query($conn, "SELECT id_peraturan, kategori, isi_peraturan FROM peraturan ORDER BY kategori, id_peraturan ASC");
while ($row = mysqli_fetch_assoc($rulesQuery)) {
    $kategori = $row['kategori'] ?: 'Kewajiban';
    if (!isset($rulesByCategory[$kategori])) {
        $rulesByCategory[$kategori] = [];
    }
    $rulesByCategory[$kategori][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peraturan Kost - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #eaf3ff, #f8fbff);
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #0f2f59, #123d75);
            position: fixed;
            left: -250px;
            top: 0;
            transition: 0.3s;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 5px 0 25px rgba(0,0,0,0.08);
            z-index: 1000;
        }

        .sidebar.active { left: 0; }
        .sidebar h2 { text-align: center; padding: 20px 10px; margin: 0; }
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: #dbe9ff;
            text-decoration: none;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: rgba(255,255,255,0.1);
            padding-left: 28px;
        }

        .sidebar a.active {
            background: rgba(255,255,255,0.14);
            color: #ffffff;
            margin: 6px 12px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 14px;
            backdrop-filter: blur(6px);
        }

        .menu-bawah a { background: #0d2c54; }

        .main {
            margin-left: 0;
            padding: 35px;
            transition: 0.3s;
        }

        .main.shift { margin-left: 250px; }

        .header {
            background: linear-gradient(90deg, #4da6ff, #2f80ed);
            color: white;
            padding: 22px 26px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 18px;
            box-shadow: 0 10px 20px rgba(47,128,237,0.2);
            margin-bottom: 30px;
        }

        .menu-icon { cursor: pointer; font-size: 20px; padding: 10px; }
        .header-text h1, .header-text p { margin: 0; }
        .header-text p { margin-top: 6px; }

        .rule-container {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 25px;
        }

        .rule-card,
        .alert-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .full-width,
        .alert-box {
            grid-column: span 2;
        }

        .alert-box {
            background: #fff5f5;
            border-left: 5px solid #ff4d4d;
            color: #c0392b;
        }

        .rule-card h3,
        .alert-box h3 {
            margin-top: 0;
            color: #123d75;
            border-bottom: 2px solid #4da6ff;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-box h3 {
            color: #c0392b;
            border-bottom-color: rgba(255,77,77,0.35);
        }

        .rule-list {
            padding-left: 20px;
            line-height: 1.8;
            color: #444;
            margin: 0;
        }

        .rule-list li { margin-bottom: 10px; }

        .rule-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 10px;
        }

        .rule-table th,
        .rule-table td {
            padding: 12px 14px;
            border: 1px solid #eaf0f7;
            text-align: left;
            vertical-align: top;
        }

        .rule-table th {
            background: #f8fbff;
            color: #123d75;
        }

        .empty-note {
            margin: 0;
            color: #6f8094;
            font-style: italic;
        }

        .footer-note {
            margin-top: 30px;
            text-align: center;
            color: #888;
            font-style: italic;
        }

        .overlay {
            position: fixed;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            display: none;
            top: 0;
            left: 0;
            z-index: 999;
        }

        .overlay.active { display: block; }

        @media (max-width: 768px) {
            .sidebar {
                width: 70%;
                left: -70%;
            }

            .rule-container {
                grid-template-columns: 1fr;
            }

            .alert-box,
            .full-width {
                grid-column: span 1;
            }

            .main {
                padding: 20px;
            }

            .main.shift {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<?php renderTenantSidebar('penyewa_peraturan.php'); ?>

<div class="main" id="main">
    <div class="header">
        <div class="menu-icon" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </div>
        <i class="fa-solid fa-clipboard-list" style="font-size: 34px;"></i>
        <div class="header-text">
            <h1>Tata Tertib & Peraturan</h1>
            <p>Harap dipatuhi demi kenyamanan bersama di Budi Homestay</p>
        </div>
    </div>

    <div class="rule-container">
        <?php foreach ($ruleGroups as $categoryKey => $config): ?>
            <?php
            $listTag = $config['list_tag'];
            $items = $rulesByCategory[$categoryKey] ?? [];
            ?>
            <div class="<?= htmlspecialchars($config['container_class']); ?>">
                <h3><i class="<?= htmlspecialchars($config['icon']); ?>"></i> <?= htmlspecialchars($config['title']); ?></h3>
                <?php if (!empty($config['description'])): ?>
                    <p><?= htmlspecialchars($config['description']); ?></p>
                <?php endif; ?>

                <?php if ($categoryKey === 'Denda & Sanksi'): ?>
                    <table class="rule-table" style="<?= empty($items) ? 'display:none;' : ''; ?>">
                        <thead>
                            <tr>
                                <th>Jenis Pelanggaran</th>
                                <th>Sanksi / Denda</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $rule): ?>
                                <?php [$jenisPelanggaran, $sanksiDenda] = parseDendaRule($rule['isi_peraturan']); ?>
                                <tr>
                                    <td><?= htmlspecialchars($jenisPelanggaran); ?></td>
                                    <td><?= htmlspecialchars($sanksiDenda); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <<?= $listTag; ?> class="rule-list" style="<?= empty($items) ? 'display:none;' : ''; ?>">
                        <?php foreach ($items as $rule): ?>
                            <li><?= htmlspecialchars($rule['isi_peraturan']); ?></li>
                        <?php endforeach; ?>
                    </<?= $listTag; ?>>
                <?php endif; ?>
                <p class="empty-note" style="<?= empty($items) ? '' : 'display:none;'; ?>">Belum ada aturan di bagian ini.</p>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="footer-note">Peraturan ini dapat berubah sewaktu-waktu sesuai kebijakan pengelola Budi Homestay.</p>
</div>

<script>
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const main = document.getElementById("main");

function syncSidebarLayout() {
    if (window.innerWidth > 768 && sidebar.classList.contains("active")) {
        main.classList.add("shift");
        overlay.classList.remove("active");
        return;
    }

    main.classList.remove("shift");
}

function toggleSidebar() {
    sidebar.classList.toggle("active");

    if (window.innerWidth <= 768) {
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
