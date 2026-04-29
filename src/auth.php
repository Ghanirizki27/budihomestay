<?php

function redirectByRole(): void
{
    if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
        header("Location: login.php");
        exit;
    }

    if (($_SESSION['role'] ?? '') === 'penyewa') {
        header("Location: home_penyewa.php");
        exit;
    }

    header("Location: dashboard.php");
    exit;
}

function requireLogin(): void
{
    if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
        header("Location: login.php");
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();

    if (($_SESSION['role'] ?? '') !== $role) {
        redirectByRole();
    }
}
