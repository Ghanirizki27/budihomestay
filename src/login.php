<?php
session_start();
include "koneksi.php";

if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("Location: dashboard.php");
    exit;
}

$error = false;

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);

    $query = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username' AND password='$password'");
    
    if (mysqli_num_rows($query) > 0) {

        $data = mysqli_fetch_assoc($query);

        $_SESSION['status'] = "login";
        $_SESSION['id_admin'] = $data['id_admin'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['nama'] = $data['nama_lengkap'];

        header("Location: dashboard.php");
        exit;

    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Login - Budi Homestay</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    margin: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: 'Segoe UI', sans-serif;

    /* 🔥 Background putih */
    background: #ffffff;
}

/* CONTAINER */
.login-form {
    background: white;
    padding: 40px;
    border-radius: 18px;
    width: 320px;
    text-align: center;
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
}

/* LOGO */
.logo video {
    width: 110px;
    margin-bottom: 15px;
    border-radius: 10px;
}

/* TITLE */
h2 {
    margin-bottom: 20px;
    color: #0f2f59;
}

/* INPUT */
input {
    width: 100%;
    padding: 14px;
    border-radius: 10px;
    border: 1px solid #ddd;
    margin-bottom: 15px;
    outline: none;
    font-size: 14px;
}

input:focus {
    border-color: #2f80ed;
}

/* PASSWORD ICON */
.input-group {
    position: relative;
}
.input-group i {
    position: absolute;
    right: 12px;
    top: 42%;
    transform: translateY(-50%);
    color: #555;
    cursor: pointer;
}

/* BUTTON */
button {
    width: 100%;
    padding: 12px 20px; ;
    background: linear-gradient(90deg, #4da6ff, #2f80ed);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;

}

button:hover {
    opacity: 0.9;
}

/* ERROR */
.error-msg {
    color: #ff4c4c;
    margin-bottom: 10px;
    font-size: 14px;
}
</style>
</head>

<body>

<form class="login-form" method="POST">

    <!-- 🎥 LOGO VIDEO -->
    <div class="logo">
    <video autoplay loop muted playsinline>
        <source src="Budi.mp4" type="video/mp4">
    </video>
</div>

<h2 class="title">Budi Homestay</h2>

    <?php if($error): ?>
        <div class="error-msg">Username / Password salah</div>
    <?php endif; ?>

    <input type="text" name="username" placeholder="Username" required>

    <div class="input-group">
        <input type="password" name="password" id="password" placeholder="Password" required>
        <i class="fa-solid fa-eye" id="togglePassword"></i>
    </div>

    <button name="login">Masuk</button>
</form>

<script>
// Toggle password
const togglePassword = document.getElementById('togglePassword');
const password = document.getElementById('password');

togglePassword.addEventListener('click', function () {
    const type = password.type === 'password' ? 'text' : 'password';
    password.type = type;
    this.classList.toggle('fa-eye');
    this.classList.toggle('fa-eye-slash');
});
</script>

</body>
</html>