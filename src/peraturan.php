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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'update_rule') {
        $id = (int) ($_POST['id_peraturan'] ?? 0);
        $isi = trim($_POST['isi_peraturan'] ?? '');

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data peraturan tidak valid.']);
            exit;
        }

        [$jenisDenda, $sanksiDenda] = parseDendaRule($isi);
        if ($isi === DENDA_SEPARATOR || ($jenisDenda === '' && $sanksiDenda === '')) {
            $isi = '';
        }

        if ($isi === '') {
            mysqli_query($conn, "DELETE FROM peraturan WHERE id_peraturan = $id");
            echo json_encode(['success' => true, 'deleted' => true]);
            exit;
        }

        $isiEscaped = mysqli_real_escape_string($conn, $isi);
        mysqli_query($conn, "
            UPDATE peraturan
            SET isi_peraturan = '$isiEscaped'
            WHERE id_peraturan = $id
        ");

        echo json_encode(['success' => mysqli_affected_rows($conn) >= 0, 'deleted' => false]);
        exit;
    }

    if ($action === 'delete_rule') {
        $id = (int) ($_POST['id_peraturan'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data peraturan tidak valid.']);
            exit;
        }

        mysqli_query($conn, "DELETE FROM peraturan WHERE id_peraturan = $id");
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add_rule') {
        $kategori = trim($_POST['kategori'] ?? '');
        $isi = trim($_POST['isi_peraturan'] ?? '');
        $jenisPelanggaran = trim($_POST['jenis_pelanggaran'] ?? '');
        $sanksiDenda = trim($_POST['sanksi_denda'] ?? '');

        if ($kategori === 'Denda & Sanksi') {
            $isi = joinDendaRule($jenisPelanggaran, $sanksiDenda);
        }

        if (!isset($ruleGroups[$kategori]) || $isi === '') {
            echo json_encode(['success' => false, 'message' => 'Kategori atau isi peraturan tidak valid.']);
            exit;
        }

        $kategoriEscaped = mysqli_real_escape_string($conn, $kategori);
        $isiEscaped = mysqli_real_escape_string($conn, $isi);

        mysqli_query($conn, "
            INSERT INTO peraturan (kategori, isi_peraturan)
            VALUES ('$kategoriEscaped', '$isiEscaped')
        ");

        $newId = mysqli_insert_id($conn);
        echo json_encode([
            'success' => $newId > 0,
            'id_peraturan' => $newId,
            'isi_peraturan' => htmlspecialchars($isi, ENT_QUOTES, 'UTF-8'),
            'parsed_denda' => $kategori === 'Denda & Sanksi' ? parseDendaRule($isi) : null,
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
    exit;
}

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
            justify-content: space-between;
            gap: 18px;
            box-shadow: 0 10px 20px rgba(47,128,237,0.2);
            margin-bottom: 18px;
        }

        .menu-icon { cursor: pointer; font-size: 20px; padding: 10px; }
        .header-text h1, .header-text p { margin: 0; }
        .header-text p { margin-top: 6px; }

        .helper-text {
            margin: 0 0 24px;
            color: #49617e;
            background: rgba(255,255,255,0.75);
            border: 1px solid rgba(77,166,255,0.16);
            padding: 14px 18px;
            border-radius: 14px;
        }

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
            padding-left: 22px;
            line-height: 1.8;
            color: #444;
            margin: 0;
        }

        .rule-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 10px;
            overflow: hidden;
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

        .rule-row {
            margin-bottom: 10px;
            padding-right: 12px;
        }

        .rule-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .rule-item.table-item {
            align-items: stretch;
        }

        .rule-text {
            flex: 1;
            min-height: 24px;
            padding: 2px 6px;
            border-radius: 8px;
            transition: background 0.2s, box-shadow 0.2s;
        }

        .rule-text:hover {
            background: rgba(77,166,255,0.08);
        }

        .rule-text.editing {
            background: #ffffff;
            box-shadow: 0 0 0 2px rgba(77,166,255,0.28);
            outline: none;
        }

        .rule-cell {
            width: 100%;
        }

        .delete-btn {
            border: none;
            background: rgba(255,77,77,0.12);
            color: #d63031;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            cursor: pointer;
            flex-shrink: 0;
            transition: 0.2s;
        }

        .delete-btn:hover {
            background: rgba(255,77,77,0.2);
        }

        .add-rule-form {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }

        .add-rule-form.table-form {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) auto;
        }

        .add-rule-input {
            flex: 1;
            border: 1px solid #cfe0f5;
            border-radius: 10px;
            padding: 12px 14px;
            font: inherit;
        }

        .add-rule-btn {
            border: none;
            background: linear-gradient(90deg, #4da6ff, #2f80ed);
            color: white;
            border-radius: 10px;
            padding: 0 18px;
            font-weight: 700;
            cursor: pointer;
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
                z-index: 1000;
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

            .add-rule-form {
                flex-direction: column;
            }

            .add-rule-btn {
                height: 46px;
            }
        }
    </style>
</head>
<body>
<?php renderAdminSidebar('peraturan.php'); ?>

<div class="main" id="main">
    <div class="header">
        <div style="display:flex; align-items:center; gap:18px;">
            <div class="menu-icon" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </div>
            <i class="fa-solid fa-clipboard-list" style="font-size: 34px;"></i>
            <div class="header-text">
                <h1>Tata Tertib & Peraturan</h1>
                <p>Harap dipatuhi demi kenyamanan bersama di Budi Homestay</p>
            </div>
        </div>
    </div>

    <p class="helper-text">Double klik teks aturan untuk edit langsung. Jika teks dikosongkan lalu klik di luar, aturan akan terhapus.</p>

    <div class="rule-container">
        <?php foreach ($ruleGroups as $categoryKey => $config): ?>
            <?php
            $listTag = $config['list_tag'];
            $items = $rulesByCategory[$categoryKey] ?? [];
            ?>
            <div class="<?= htmlspecialchars($config['container_class']); ?>" data-category="<?= htmlspecialchars($categoryKey); ?>">
                <h3><i class="<?= htmlspecialchars($config['icon']); ?>"></i> <?= htmlspecialchars($config['title']); ?></h3>
                <?php if (!empty($config['description'])): ?>
                    <p><?= htmlspecialchars($config['description']); ?></p>
                <?php endif; ?>

                <?php if ($categoryKey === 'Denda & Sanksi'): ?>
                    <table class="rule-table" data-list="<?= htmlspecialchars($categoryKey); ?>">
                        <thead>
                            <tr>
                                <th>Jenis Pelanggaran</th>
                                <th>Sanksi / Denda</th>
                                <th style="width:60px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $rule): ?>
                                <?php [$jenisPelanggaran, $sanksiDenda] = parseDendaRule($rule['isi_peraturan']); ?>
                                <tr class="rule-row" data-rule-id="<?= (int) $rule['id_peraturan']; ?>" data-rule-kind="denda">
                                    <td>
                                        <span class="rule-text rule-cell" data-rule-text data-field="jenis"><?= htmlspecialchars($jenisPelanggaran); ?></span>
                                    </td>
                                    <td>
                                        <span class="rule-text rule-cell" data-rule-text data-field="sanksi"><?= htmlspecialchars($sanksiDenda); ?></span>
                                    </td>
                                    <td>
                                        <button type="button" class="delete-btn" data-delete-rule title="Hapus aturan">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <<?= $listTag; ?> class="rule-list" data-list="<?= htmlspecialchars($categoryKey); ?>">
                        <?php foreach ($items as $rule): ?>
                            <li class="rule-row" data-rule-id="<?= (int) $rule['id_peraturan']; ?>">
                                <div class="rule-item">
                                    <span class="rule-text" data-rule-text><?= htmlspecialchars($rule['isi_peraturan']); ?></span>
                                    <button type="button" class="delete-btn" data-delete-rule title="Hapus aturan">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </<?= $listTag; ?>>
                <?php endif; ?>

                <p class="empty-note" data-empty-note style="<?= empty($items) ? '' : 'display:none;'; ?>">Belum ada aturan di bagian ini.</p>

                <form class="add-rule-form <?= $categoryKey === 'Denda & Sanksi' ? 'table-form' : ''; ?>" data-add-form>
                    <input type="hidden" name="kategori" value="<?= htmlspecialchars($categoryKey); ?>">
                    <?php if ($categoryKey === 'Denda & Sanksi'): ?>
                        <input
                            type="text"
                            name="jenis_pelanggaran"
                            class="add-rule-input"
                            placeholder="Jenis pelanggaran"
                            autocomplete="off"
                            required
                        >
                        <input
                            type="text"
                            name="sanksi_denda"
                            class="add-rule-input"
                            placeholder="Sanksi / denda"
                            autocomplete="off"
                            required
                        >
                    <?php else: ?>
                        <input
                            type="text"
                            name="isi_peraturan"
                            class="add-rule-input"
                            placeholder="Tambah aturan baru..."
                            autocomplete="off"
                            required
                        >
                    <?php endif; ?>
                    <button type="submit" class="add-rule-btn">Tambah</button>
                </form>
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

async function sendRuleRequest(payload) {
    const response = await fetch("peraturan.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams(payload),
    });

    return response.json();
}

function attachEditableBehavior(element) {
    const initialText = element.textContent.trim();

    element.addEventListener("dblclick", () => {
        element.dataset.originalValue = element.textContent.trim();
        element.contentEditable = "true";
        element.classList.add("editing");
        element.focus();

        const selection = window.getSelection();
        const range = document.createRange();
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
    });

    element.addEventListener("keydown", async (event) => {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            element.blur();
        }

        if (event.key === "Escape") {
            event.preventDefault();
            element.textContent = element.dataset.originalValue || initialText;
            element.blur();
        }
    });

    element.addEventListener("blur", async () => {
        if (element.contentEditable !== "true") {
            return;
        }

        const listItem = element.closest("[data-rule-id]");
        const originalValue = element.dataset.originalValue || "";
        const updatedValue = element.textContent.trim();

        element.contentEditable = "false";
        element.classList.remove("editing");

        if (updatedValue === originalValue) {
            return;
        }

        let payloadValue = updatedValue;
        if (listItem.dataset.ruleKind === "denda") {
            const jenis = listItem.querySelector('[data-field="jenis"]').textContent.trim();
            const sanksi = listItem.querySelector('[data-field="sanksi"]').textContent.trim();
            payloadValue = `${jenis} ||| ${sanksi}`;
        }

        const result = await sendRuleRequest({
            action: "update_rule",
            id_peraturan: listItem.dataset.ruleId,
            isi_peraturan: payloadValue,
        });

        if (!result.success) {
            alert(result.message || "Perubahan gagal disimpan.");
            element.textContent = originalValue;
            return;
        }

        if (result.deleted) {
            listItem.remove();
            toggleEmptyState(listItem.closest("[data-category]"));
        } else {
            element.textContent = updatedValue;
        }
    });
}

function toggleEmptyState(section) {
    const list = section.querySelector("[data-list]");
    const emptyNote = section.querySelector("[data-empty-note]");
    const hasItems = list.querySelectorAll("[data-rule-id]").length > 0;

    if (emptyNote) {
        emptyNote.style.display = hasItems ? "none" : "block";
    }

    if (list.tagName === "TABLE") {
        list.style.display = hasItems ? "table" : "none";
    } else {
        list.style.display = hasItems ? "" : "none";
    }
}

function buildRuleItem(id, text) {
    const item = document.createElement("li");
    item.className = "rule-row";
    item.dataset.ruleId = id;
    item.innerHTML = `
        <div class="rule-item">
            <span class="rule-text" data-rule-text></span>
            <button type="button" class="delete-btn" data-delete-rule title="Hapus aturan">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    `;
    item.querySelector("[data-rule-text]").textContent = text;
    attachEditableBehavior(item.querySelector("[data-rule-text]"));
    return item;
}

function buildDendaRow(id, jenis, sanksi) {
    const row = document.createElement("tr");
    row.className = "rule-row";
    row.dataset.ruleId = id;
    row.dataset.ruleKind = "denda";
    row.innerHTML = `
        <td><span class="rule-text rule-cell" data-rule-text data-field="jenis"></span></td>
        <td><span class="rule-text rule-cell" data-rule-text data-field="sanksi"></span></td>
        <td>
            <button type="button" class="delete-btn" data-delete-rule title="Hapus aturan">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    `;
    row.querySelector('[data-field="jenis"]').textContent = jenis;
    row.querySelector('[data-field="sanksi"]').textContent = sanksi;
    row.querySelectorAll("[data-rule-text]").forEach(attachEditableBehavior);
    return row;
}

document.querySelectorAll("[data-rule-text]").forEach(attachEditableBehavior);

document.addEventListener("click", async (event) => {
    const deleteButton = event.target.closest("[data-delete-rule]");
    if (!deleteButton) {
        return;
    }

    const listItem = deleteButton.closest("[data-rule-id]");
    const section = deleteButton.closest("[data-category]");

    if (!confirm("Hapus aturan ini?")) {
        return;
    }

    const result = await sendRuleRequest({
        action: "delete_rule",
        id_peraturan: listItem.dataset.ruleId,
    });

    if (!result.success) {
        alert(result.message || "Aturan gagal dihapus.");
        return;
    }

    listItem.remove();
    toggleEmptyState(section);
});

document.querySelectorAll("[data-add-form]").forEach((form) => {
    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const section = form.closest("[data-category]");
        const list = section.querySelector("[data-list]");
        const category = form.querySelector("input[name='kategori']").value;
        let payload;

        if (category === "Denda & Sanksi") {
            const jenisInput = form.querySelector("input[name='jenis_pelanggaran']");
            const sanksiInput = form.querySelector("input[name='sanksi_denda']");
            const jenisValue = jenisInput.value.trim();
            const sanksiValue = sanksiInput.value.trim();

            if (!jenisValue || !sanksiValue) {
                (!jenisValue ? jenisInput : sanksiInput).focus();
                return;
            }

            payload = {
                action: "add_rule",
                kategori: category,
                jenis_pelanggaran: jenisValue,
                sanksi_denda: sanksiValue,
            };
        } else {
            const input = form.querySelector("input[name='isi_peraturan']");
            const value = input.value.trim();

            if (!value) {
                input.focus();
                return;
            }

            payload = {
                action: "add_rule",
                kategori: category,
                isi_peraturan: value,
            };
        }

        const result = await sendRuleRequest(payload);

        if (!result.success) {
            alert(result.message || "Aturan gagal ditambahkan.");
            return;
        }

        if (category === "Denda & Sanksi") {
            const [jenisValue, sanksiValue] = result.parsed_denda || ["", ""];
            list.querySelector("tbody").appendChild(buildDendaRow(result.id_peraturan, jenisValue, sanksiValue));
            form.querySelector("input[name='jenis_pelanggaran']").value = "";
            form.querySelector("input[name='sanksi_denda']").value = "";
            form.querySelector("input[name='jenis_pelanggaran']").focus();
        } else {
            const input = form.querySelector("input[name='isi_peraturan']");
            list.appendChild(buildRuleItem(result.id_peraturan, input.value.trim()));
            input.value = "";
            input.focus();
        }

        toggleEmptyState(section);
    });
});

document.querySelectorAll("[data-category]").forEach(toggleEmptyState);

window.addEventListener("resize", syncSidebarLayout);
syncSidebarLayout();
</script>

</body>
</html>
