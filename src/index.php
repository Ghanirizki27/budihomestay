<?php
include 'koneksi.php';
// Koneksi ke Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "budihomestay";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Ambil data dari tabel kamar
$sql = "SELECT * FROM kamar";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kos-kosan</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .status-tersedia { color: green; font-weight: bold; }
        .status-terisi { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <h2>Manajemen Kamar Kos</h2>
    
    <table>
        <thead>
            <tr>
                <th>No. Kamar</th>
                <th>Tipe</th>
                <th>Harga/Bulan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    $statusClass = ($row['status_kamar'] == 'Tersedia') ? 'status-tersedia' : 'status-terisi';
                    echo "<tr>
                            <td>" . $row['nomor_kamar'] . "</td>
                            <td>" . $row['tipe_kamar'] . "</td>
                            <td>Rp " . number_format($row['harga_per_bulan'], 0, ',', '.') . "</td>
                            <td class='$statusClass'>" . $row['status_kamar'] . "</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='4'>Belum ada data kamar.</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>