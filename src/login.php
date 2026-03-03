<?php
session_start();
include "koneksi.php";

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("Location: dashboard.php");
    exit;
}

$error = false;

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']); // karena database pakai md5

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Selamat Datang</title>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
        /* [DEFAULT - LAMPU MATI] Background hanya warna gelap polos */
        body { 
            margin: 0; 
            padding: 0; 
            height: 100vh; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center; 
            background-color: #0a0b0d; 
            background-image: none; /* Tidak ada foto saat mati */
            font-family: sans-serif; 
            color: white; 
            overflow: hidden; 
            transition: background 0.3s ease; /* Transisi halus */
        }

        /* [LAMPU MENYALA] Foto muncul */
        body[data-on="true"] { 
            /* Saya pakai link foto dari internet. Nanti ganti link di dalam url() dengan 'nama_foto_kamu.jpg' */
            background-image: linear-gradient(rgba(10, 11, 13, 0.5), rgba(10, 11, 13, 0.5)), url('bg-kos.jpg'); 
            /* GANTI BAGIAN INI */
            background-size: 100% 100%; /* Pilihan Cara 3: Pas ke kotak layar */
            
            background-position: center;
            background-repeat: no-repeat;
        }
        

        .lamp-container { position: absolute; top: 0; left: 50%; transform: translateX(-50%); display: flex; flex-direction: column; align-items: center; z-index: 10; }
        .wire { width: 4px; height: 120px; background-color: #333; }
        .lamp-shade { width: 100px; height: 40px; background-color: #2c303a; border-radius: 50px 50px 0 0; position: relative; }
        .bulb { width: 50px; height: 50px; background-color: #222; border-radius: 50%; position: absolute; bottom: -25px; left: 25px; z-index: -1; transition: all 0.3s ease; }
        .pull-string { position: absolute; right: 15px; top: 20px; width: 3px; height: 100px; background-color: #777; cursor: pointer; transform-origin: top; transition: transform 0.1s; }
        .pull-string::after { content: ''; position: absolute; bottom: -10px; left: -4.5px; width: 12px; height: 12px; background-color: #ff9800; border-radius: 50%; }
        .pull-string:active { transform: scaleY(1.3); }
        body[data-on="true"] .bulb { background-color: #ffe600; box-shadow: 0 0 80px 30px rgba(206, 187, 14, 0.4), 0 0 200px 80px rgba(189, 171, 40, 0.2); }
        .login-form { background-color: rgb(12, 4, 59); backdrop-filter: blur(10px); padding: 40px; border-radius: 15px; text-align: center; width: 300px; box-shadow: 0 10px 30px rgb(38, 74, 153); border: 1px solid rgba(5, 18, 90, 0.1); opacity: 0; transform: translateY(50px); pointer-events: none; margin-top: 100px; }
        .input-group { position: relative; width: 100%; margin: 15px 0; }
        .login-form input { display: block; width: 100%; margin: 0; padding: 12px; padding-right: 40px; border-radius: 8px; border: none; background-color: rgba(255, 255, 255, 0.9); box-sizing: border-box; outline: none; }
        .input-group i { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #555; font-size: 16px; }
        .username-input { margin: 15px 0 !important; }
        .login-form button { width: 100%; padding: 12px; background-color: #ff9800; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 5px; }
        .login-form button:hover { background-color: #e68a00; }
        .error-msg { color: #ff4c4c; font-size: 14px; margin-bottom: 10px; display: block; }
    </style>
</head>
<body>

<div class="lamp-container">
    <div class="wire"></div>
    <div class="lamp-shade">
        <div class="bulb"></div>
        <div class="pull-string" onclick="toggleLamp()"></div>
    </div>
</div>

<form class="login-form" method="POST">
    <h2>Selamat Datang </h2>

    <?php if($error): ?>
        <div class="error-msg">Username atau Password salah!</div>
    <?php endif; ?>

    <input type="text" name="username" class="username-input" placeholder="Username" required>

    <div class="input-group">
        <input type="password" name="password" id="password" placeholder="Password" required>
        <i class="fa-solid fa-eye" id="togglePassword"></i>
    </div>

    <button type="submit" name="login">Masuk</button>
</form>

<script>
let isOn = false;
const body = document.body;
const loginForm = document.querySelector('.login-form');

function toggleLamp() {
    isOn = !isOn;
    body.setAttribute('data-on', isOn);

    if (isOn) {
        gsap.to(loginForm, {opacity:1, y:0, duration:0.6});
        loginForm.style.pointerEvents = "auto";
    } else {
        gsap.to(loginForm, {opacity:0, y:50, duration:0.4});
        loginForm.style.pointerEvents = "none";
    }
}

// Toggle password
const togglePassword = document.querySelector('#togglePassword');
const password = document.querySelector('#password');

togglePassword.addEventListener('click', function () {
    const type = password.type === 'password' ? 'text' : 'password';
    password.type = type;
    this.classList.toggle('fa-eye');
    this.classList.toggle('fa-eye-slash');
});

// Jika error, otomatis nyalakan lampu
<?php if($error): ?>
toggleLamp();
<?php endif; ?>
</script>

</body>
</html>