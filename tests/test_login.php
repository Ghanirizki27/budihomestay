<?php
// Masukkan password yang ingin kamu tes di sini
$password_asli = "12345"; 

$password_md5 = md5($password_asli);

echo "<h2>Hasil Enkripsi MD5</h2>";
echo "Password Asli: <b>$password_asli</b><br>";
echo "Hasil MD5 (Copy ini ke kolom password di phpMyAdmin): <br>";
echo "<input type='text' value='$password_md5' style='width:300px;' readonly>";
echo "<p>Pastikan di database kamu, kolom password memiliki panjang (Length) minimal 32 karakter.</p>";

session_start();

// Simulasi data dari database
$_SESSION['status'] = "login";
$_SESSION['id_admin'] = "1";
$_SESSION['username'] = "admin_tester";
$_SESSION['nama'] = "Tester Budi Homestay";

echo "<h2>Session Berhasil Dibuat!</h2>";
echo "Anda sekarang dianggap sudah login.<br>";
echo "<a href='dashboard.php' style='padding:10px; background:orange; color:white; text-decoration:none; border-radius:5px;
'>Lanjut ke Dashboard</a>";

?>