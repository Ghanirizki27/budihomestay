<?php
session_start();
include_once "auth.php";
include_once "koneksi.php";
include_once "db_migrations.php";
include_once "admin_nav.php";

requireRole('admin');
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

function joinDendaRule(string $jenis, string $sanksi): string
{
    return trim($jenis) . ' ' . DENDA_SEPARATOR . ' ' . trim($sanksi);
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
        'list_tag' => 'ul',
        'description' => 'Penghuni dapat dikeluarkan tanpa pengembalian uang sewa jika terbukti:',
    ],
    'Denda & Sanksi' => [
        'title' => 'Denda & Sanksi Pelanggaran',
        'icon' => 'fa-solid fa-gavel',
        'container_class' => 'rule-card full-width',
        'list_tag' => 'table',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'update_rule') {
        $id = (int) ($_POST['id_peraturan'] ?? 0);
        $isi = trim($_POST['isi_peraturan'] ?? '');
        if ($id <= 0) { echo json_encode(['success' => false]); exit; }

        [$jenisDenda, $sanksiDenda] = parseDendaRule($isi);
        if ($isi === DENDA_SEPARATOR || ($jenisDenda === '' && $sanksiDenda === '')) { $isi = ''; }

        if ($isi === '') {
            mysqli_query($conn, "DELETE FROM peraturan WHERE id_peraturan = $id");
            echo json_encode(['success' => true, 'deleted' => true]); exit;
        }

        $isiEscaped = mysqli_real_escape_string($conn, $isi);
        mysqli_query($conn, "UPDATE peraturan SET isi_peraturan = '$isiEscaped' WHERE id_peraturan = $id");
        echo json_encode(['success' => true, 'deleted' => false]); exit;
    }

    if ($action === 'delete_rule') {
        $id = (int) ($_POST['id_peraturan'] ?? 0);
        mysqli_query($conn, "DELETE FROM peraturan WHERE id_peraturan = $id");
        echo json_encode(['success' => true]); exit;
    }

    if ($action === 'add_rule') {
        $kategori = trim($_POST['kategori'] ?? '');
        $isi = trim($_POST['isi_peraturan'] ?? '');
        if ($kategori === 'Denda & Sanksi') {
            $isi = joinDendaRule(trim($_POST['jenis_pelanggaran'] ?? ''), trim($_POST['sanksi_denda'] ?? ''));
        }

        if (!isset($ruleGroups[$kategori]) || $isi === '') { echo json_encode(['success' => false]); exit; }

        $kategoriEscaped = mysqli_real_escape_string($conn, $kategori);
        $isiEscaped = mysqli_real_escape_string($conn, $isi);
        mysqli_query($conn, "INSERT INTO peraturan (kategori, isi_peraturan) VALUES ('$kategoriEscaped', '$isiEscaped')");
        
        echo json_encode(['success' => true]); exit;
    }
}

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
        .menu-icon { cursor: pointer; padding: 10px; border-radius: 10px; font-size: 20px; background: rgba(255,255,255,0.15); display: flex; }

        /* HELPER TEXT */
        .helper-text { background: white; border: 1px solid #e1e9f4; padding: 15px 20px; border-radius: 12px; color: #637892; font-size: 14px; margin: 24px 0; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }

        /* RULE CARDS (LAYOUT SEPERTI GAMBAR) */
        .rule-container { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        .rule-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.04); }
        .full-width { grid-column: span 2; }
        
        /* ALERT BOX (LARANGAN KERAS) */
        .alert-box { background: #fffcfc; border: 1px solid #ffeeee; border-left: 5px solid #eb5757; padding: 25px; border-radius: 15px; grid-column: span 2; box-shadow: 0 5px 15px rgba(0,0,0,0.04); }
        
        /* CARD HEADERS */
        .rule-card h3 { color: #0f3c74; font-size: 16px; margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #2f80ed; display: flex; align-items: center; gap: 10px; }
        .alert-box h3 { color: #c0392b; font-size: 16px; margin-top: 0; padding-bottom: 15px; border-bottom: 1px solid rgba(235,87,87,0.3); display: flex; align-items: center; gap: 10px; }

        /* LIST & ITEMS */
        .rule-list { padding-left: 20px; margin: 15px 0 25px 0; color: #444; }
        .rule-item { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; gap: 15px; }
        .rule-text { flex: 1; padding: 4px 8px; border-radius: 6px; cursor: pointer; transition: 0.2s; line-height: 1.6; }
        .rule-text:hover { background: #f0f7ff; color: #2f80ed; }
        .rule-text.editing { background: white; box-shadow: 0 0 0 2px #2f80ed; outline: none; }
        
        /* DELETE BUTTON (MERAH KOTAK KECIL) */
        .delete-btn { background: #fff1f1; color: #eb5757; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; flex-shrink: 0; }
        .delete-btn:hover { background: #eb5757; color: white; }

        /* ADD FORM (DI BAWAH) */
        .add-rule-form { display: flex; gap: 10px; margin-top: auto; }
        .add-rule-input { flex: 1; padding: 12px 15px; border: 1px solid #ccd7e5; border-radius: 10px; font-family: inherit; font-size: 14px; }
        .add-rule-btn { background: #2f80ed; color: white; border: none; padding: 0 20px; border-radius: 10px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .add-rule-btn:hover { background: #1f6fd6; }

        /* TABLE FOR DENDA */
        .rule-table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 25px; }
        .rule-table th { text-align: left; background: #f8fbff; padding: 12px; color: #5c6f87; font-size: 13px; border-bottom: 1px solid #e1e9f4; }
        .rule-table td { padding: 12px; border-bottom: 1px solid #f0f4f8; }

        .empty-note { color: #8898aa; font-style: italic; font-size: 14px; margin-bottom: 20px; }

        @media (max-width: 900px) { .rule-container { grid-template-columns: 1fr; } .full-width, .alert-box { grid-column: span 1; } .main.shift { margin-left: 0; } .header { flex-direction: column; align-items: flex-start; } .add-rule-form { flex-direction: column; } .add-rule-btn { padding: 12px; } }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    <?php renderAdminSidebar('peraturan.php'); ?>

    <div class="main" id="main">
        <!-- HEADER KONSISTEN -->
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px;">
                <div class="menu-icon" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
                <div>
                    <h1 style="font-size: 24px; margin:0;">Tata Tertib & Peraturan</h1>
                    <p style="margin:5px 0 0; opacity:0.8; font-size: 14px;">Harap dipatuhi demi kenyamanan bersama di Budi Homestay.</p>
                </div>
            </div>
            <div class="header-info-right" style="text-align: right;">
                <div id="tanggal" style="font-weight: 600; font-size: 13px;"></div>
                <div id="waktu" style="font-size: 22px; font-weight: 800; margin: 2px 0;"></div>
                <div style="opacity: 0.8; font-size: 12px; font-weight: 600;">Level: Administrator</div>
            </div>
        </div>

        <!-- HELPER TEXT SEPERTI GAMBAR -->
        <div class="helper-text">
            Double klik teks aturan untuk edit langsung. Jika teks dikosongkan lalu klik di luar, aturan akan terhapus.
        </div>

        <div class="rule-container">
            <?php foreach ($ruleGroups as $categoryKey => $config): 
                $items = $rulesByCategory[$categoryKey] ?? []; ?>
                
                <div class="<?= $config['container_class']; ?>" data-category="<?= $categoryKey; ?>" style="display:flex; flex-direction:column;">
                    <h3><i class="<?= $config['icon']; ?>"></i> <?= $config['title']; ?></h3>
                    
                    <?php if (!empty($config['description'])): ?>
                        <p style="font-size: 14px; margin-top: 0; margin-bottom: 15px; color: #c0392b;"><?= $config['description']; ?></p>
                    <?php endif; ?>

                    <?php if ($config['list_tag'] === 'table'): ?>
                        <div style="overflow-x: auto;">
                            <table class="rule-table" data-list="<?= $categoryKey; ?>">
                                <thead>
                                    <tr>
                                        <th>Jenis Pelanggaran</th>
                                        <th>Sanksi / Denda</th>
                                        <th style="width:40px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $rule): 
                                        [$jenis, $sanksi] = parseDendaRule($rule['isi_peraturan']); ?>
                                        <tr class="rule-row" data-rule-id="<?= $rule['id_peraturan']; ?>" data-rule-kind="denda">
                                            <td><span class="rule-text" data-rule-text data-field="jenis" style="display:block;"><?= htmlspecialchars($jenis); ?></span></td>
                                            <td><span class="rule-text" data-rule-text data-field="sanksi" style="display:block;"><?= htmlspecialchars($sanksi); ?></span></td>
                                            <td><button type="button" class="delete-btn" data-delete-rule><i class="fa-solid fa-trash-can"></i></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <<?= $config['list_tag']; ?> class="rule-list" data-list="<?= $categoryKey; ?>">
                            <?php foreach ($items as $rule): ?>
                                <li class="rule-row" data-rule-id="<?= $rule['id_peraturan']; ?>">
                                    <div class="rule-item">
                                        <span class="rule-text" data-rule-text><?= htmlspecialchars($rule['isi_peraturan']); ?></span>
                                        <button type="button" class="delete-btn" data-delete-rule><i class="fa-solid fa-trash-can"></i></button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </<?= $config['list_tag']; ?>>
                    <?php endif; ?>

                    <p class="empty-note" data-empty-note style="<?= empty($items) ? '' : 'display:none;'; ?>">Belum ada aturan di bagian ini.</p>

                    <!-- ADD FORM DI BAGIAN BAWAH CARD -->
                    <form class="add-rule-form" data-add-form>
                        <input type="hidden" name="kategori" value="<?= $categoryKey; ?>">
                        <?php if ($categoryKey === 'Denda & Sanksi'): ?>
                            <input type="text" name="jenis_pelanggaran" class="add-rule-input" placeholder="Jenis Pelanggaran" required>
                            <input type="text" name="sanksi_denda" class="add-rule-input" placeholder="Sanksi / Denda" required>
                        <?php else: ?>
                            <input type="text" name="isi_peraturan" class="add-rule-input" placeholder="Tambah aturan baru..." required>
                        <?php endif; ?>
                        <button type="submit" class="add-rule-btn">Tambah</button>
                    </form>
                </div>
            <?php endforeach; ?>
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

    async function sendRuleRequest(payload) {
        const res = await fetch("peraturan.php", { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: new URLSearchParams(payload) });
        return res.json();
    }

    function attachEditableBehavior(el) {
        el.addEventListener("dblclick", () => {
            el.dataset.originalValue = el.textContent.trim();
            el.contentEditable = "true";
            el.classList.add("editing");
            el.focus();
        });

        el.addEventListener("blur", async () => {
            if (el.contentEditable !== "true") return;
            const listItem = el.closest("[data-rule-id]");
            const updatedValue = el.textContent.trim();
            el.contentEditable = "false";
            el.classList.remove("editing");

            if (updatedValue === el.dataset.originalValue) return;

            let payloadValue = updatedValue;
            if (listItem.dataset.ruleKind === "denda") {
                const row = el.closest("tr");
                payloadValue = row.querySelector('[data-field="jenis"]').textContent.trim() + " ||| " + row.querySelector('[data-field="sanksi"]').textContent.trim();
            }

            const res = await sendRuleRequest({ action: "update_rule", id_peraturan: listItem.dataset.ruleId, isi_peraturan: payloadValue });
            if (res.deleted) {
                const section = listItem.closest("[data-category]");
                listItem.remove();
                toggleEmptyState(section);
            }
        });
    }

    function toggleEmptyState(section) {
        const list = section.querySelector("[data-list]");
        const emptyNote = section.querySelector("[data-empty-note]");
        const hasItems = list.querySelectorAll("[data-rule-id]").length > 0;
        if (emptyNote) emptyNote.style.display = hasItems ? "none" : "block";
    }

    document.querySelectorAll("[data-rule-text]").forEach(attachEditableBehavior);

    document.addEventListener("click", async (e) => {
        const btn = e.target.closest("[data-delete-rule]");
        if (!btn || !confirm("Hapus aturan ini?")) return;
        const item = btn.closest("[data-rule-id]");
        const section = btn.closest("[data-category]");
        const res = await sendRuleRequest({ action: "delete_rule", id_peraturan: item.dataset.ruleId });
        if (res.success) {
            item.remove();
            toggleEmptyState(section);
        }
    });

    document.querySelectorAll("[data-add-form]").forEach(form => {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            formData.append("action", "add_rule");
            const res = await sendRuleRequest(Object.fromEntries(formData));
            if (res.success) location.reload(); // Reload agar DOM tampil sempurna
        });
    });

    window.addEventListener("resize", syncSidebarLayout);
    syncSidebarLayout();
    </script>
</body>
</html>