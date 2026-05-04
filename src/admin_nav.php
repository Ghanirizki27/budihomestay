<?php
function renderAdminSidebar(string $activePage): void
{
    $menus = [
        ['href' => 'dashboard.php', 'icon' => 'fa-solid fa-gauge', 'label' => 'Dashboard'],
        ['href' => 'kamar.php', 'icon' => 'fa-solid fa-bed', 'label' => 'Data Kamar'],
        ['href' => 'penghuni.php', 'icon' => 'fa-solid fa-users', 'label' => 'Data Penyewa'],
        ['href' => 'pembayaran.php', 'icon' => 'fa-solid fa-money-bill-wave', 'label' => 'Pembayaran'],
        ['href' => 'keluhan_admin.php', 'icon' => 'fa-solid fa-comments', 'label' => 'Keluhan'],
        ['href' => 'laporan.php', 'icon' => 'fa-solid fa-chart-line', 'label' => 'Laporan Keuangan'],
        
        // --- TAMBAHAN MENU BARU DI SINI ---
        ['href' => 'admin_metode.php', 'icon' => 'fa-solid fa-credit-card', 'label' => 'Metode Pembayaran'],
        
        ['href' => 'pengumuman.php', 'icon' => 'fa-solid fa-bullhorn', 'label' => 'Pengumuman'],
        ['href' => 'peraturan.php', 'icon' => 'fa-solid fa-book', 'label' => 'Peraturan Kost'],
    ];
    ?>
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <div>
            <h2><i class="fa-solid fa-house"></i> Budi Homestay</h2>

            <?php foreach ($menus as $menu): ?>
                <a
                    href="<?= htmlspecialchars($menu['href']); ?>"
                    class="<?= $activePage === $menu['href'] ? 'active' : ''; ?>"
                >
                    <i class="<?= htmlspecialchars($menu['icon']); ?>"></i>
                    <?= htmlspecialchars($menu['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="menu-bawah">
            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>
        </div>
    </div>
    <?php
}