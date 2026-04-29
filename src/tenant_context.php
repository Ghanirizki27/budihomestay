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

function getTenantPaymentSummary(mysqli $conn, int $idPenyewa, ?string $tanggalMasuk): array
{
    $summary = [
        'pembayaran_terakhir' => null,
        'jatuh_tempo' => null,
        'status_label' => 'Belum terhubung',
        'status_class' => 'status-wait',
    ];

    if ($idPenyewa <= 0 || empty($tanggalMasuk)) {
        return $summary;
    }

    $lastPaymentQuery = mysqli_query($conn, "
        SELECT tanggal_bayar
        FROM pembayaran
        WHERE id_penyewa = '$idPenyewa' AND status = 'Divalidasi'
        ORDER BY tanggal_bayar DESC
        LIMIT 1
    ");
    $lastPayment = mysqli_fetch_assoc($lastPaymentQuery);
    $pembayaranTerakhir = $lastPayment['tanggal_bayar'] ?? null;
    $baseDate = $pembayaranTerakhir ?: $tanggalMasuk;
    $jatuhTempo = date('Y-m-d', strtotime($baseDate . ' +1 month'));
    $hariIni = date('Y-m-d');
    $selisihHari = (int) floor((strtotime($hariIni) - strtotime($jatuhTempo)) / 86400);

    $statusLabel = 'Aktif';
    $statusClass = 'status-ok';
    if ($selisihHari > 0) {
        $statusLabel = 'Terlambat ' . $selisihHari . ' hari';
        $statusClass = 'status-late';
    } elseif ($hariIni === $jatuhTempo) {
        $statusLabel = 'Jatuh tempo hari ini';
        $statusClass = 'status-due';
    }

    $summary['pembayaran_terakhir'] = $pembayaranTerakhir;
    $summary['jatuh_tempo'] = $jatuhTempo;
    $summary['status_label'] = $statusLabel;
    $summary['status_class'] = $statusClass;

    return $summary;
}
