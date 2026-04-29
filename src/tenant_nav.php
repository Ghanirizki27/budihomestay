<?php

function renderTenantSidebar(string $activePage): void
{
    $menus = [
        ['href' => 'home_penyewa.php', 'icon' => 'fa-solid fa-house', 'label' => 'Dashboard'],
        ['href' => 'penyewa_pembayaran.php', 'icon' => 'fa-solid fa-wallet', 'label' => 'Pembayaran'],
        ['href' => 'penyewa_keluhan.php', 'icon' => 'fa-solid fa-comment-dots', 'label' => 'Laporan Keluhan'],
        ['href' => 'penyewa_peraturan.php', 'icon' => 'fa-solid fa-book', 'label' => 'Peraturan & Tata Tertib'],
    ];
    ?>
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <div>
            <h2><i class="fa-solid fa-house"></i> Area Penyewa</h2>

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
