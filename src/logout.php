<?php
session_start();

// Cek apakah user sudah login. Jika belum, lempar kembali ke halaman login (index.php)
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// Koneksi ke Database (Gunakan satu saja, Anda memanggil koneksi.php 2x di kode sebelumnya)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "budihomestay";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// ... SISA KODE PHP ANDA (Query kamar, penghuni, dll) ...
$kamar = mysqli_query($conn, "SELECT * FROM kamar");
$total_kamar = mysqli_num_rows($kamar);

$kosong = mysqli_query($conn, "SELECT * FROM kamar WHERE status='Kosong'");
$total_kosong = mysqli_num_rows($kosong);

$penghuni = mysqli_query($conn, "SELECT * FROM penghuni");
$total_penghuni = mysqli_num_rows($penghuni);

// Variabel untuk menandai halaman aktif di sidebar
$halaman = 'dashboard.php';
?>

<!DOCTYPE html>
<html>
<head>