<?php

function getLoggedInTenant(mysqli $conn): ?array
{
    $idAdmin = (int) ($_SESSION['id_admin'] ?? 0);
    if ($idAdmin <= 0) {
        return null;
    }

    $query = mysqli_query($conn, "
        SELECT
            penyewa.*,
            kamar.nomor_kamar,
            kamar.harga,
            admin.username AS akun_username
        FROM admin
        LEFT JOIN penyewa ON admin.id_penyewa = penyewa.id_penyewa
        LEFT JOIN kamar ON penyewa.id_kamar = kamar.id_kamar
        WHERE admin.id_admin = '$idAdmin'
        LIMIT 1
    ");

    $tenant = mysqli_fetch_assoc($query);
    return $tenant ?: null;
}

function getTenantPaymentSummary($conn, $id_penyewa, $tanggal_masuk) {
    $sekarang = new DateTime();
    
    // 1. Cari pembayaran terakhir yang sudah LUNAS
    $query_lunas = mysqli_query($conn, "
        SELECT jatuh_tempo 
        FROM pembayaran 
        WHERE id_penyewa = '$id_penyewa' AND status = 'Lunas' 
        ORDER BY jatuh_tempo DESC LIMIT 1
    ");
    $data_lunas = mysqli_fetch_assoc($query_lunas);

    if ($data_lunas) {
        // Jika ada yang lunas, jatuh tempo berikutnya adalah +1 bulan dari yang terakhir dibayar
        $last_paid_date = new DateTime($data_lunas['jatuh_tempo']);
        $next_due = clone $last_paid_date;
        $next_due->modify('+1 month');
    } else {
        // Jika belum pernah bayar lunas, jatuh tempo adalah tanggal masuk
        $next_due = new DateTime($tanggal_masuk);
    }

    $diff = $sekarang->diff($next_due);
    $is_late = $sekarang > $next_due && $diff->days > 0;
    
    // Tentukan Label dan Class CSS
    if ($is_late) {
        $status_label = "Terlambat " . $diff->days . " hari";
        $status_class = "status-late";
    } else {
        $status_label = "Aman (" . $next_due->format('d M Y') . ")";
        $status_class = "status-ok";
    }

    return [
        'jatuh_tempo' => $next_due->format('Y-m-d'),
        'status_label' => $status_label,
        'status_class' => $status_class
    ];
}