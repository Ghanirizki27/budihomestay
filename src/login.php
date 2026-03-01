<?php
session_start();
include "config.php";

$username = $_POST['username'];
$password = md5($_POST['password']);

$query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$password'");
$data  = mysqli_fetch_array($query);

if ($data) {
    $_SESSION['username'] = $data['username'];
    header("Location: dashboard.php");
} else {
    echo "<script>alert('Login gagal!'); window.location='login.php';</script>";
}
?>