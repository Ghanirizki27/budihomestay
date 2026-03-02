<?php
session_start();

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("Location: dashboard.php");
    exit;
}

$error = false;

// Proses ketika tombol login ditekan
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];


session_start();
include "koneksi.php"; // pastikan file koneksi sudah ada

// Jika sudah login
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("Location: dashboard.php");
    exit;
}

$error = false;

if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = md5($_POST['password']); // karena database pakai md5

    // Cek ke database
    $query = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username' AND password='$password'");
    $cek = mysqli_num_rows($query);

    if ($cek > 0) {

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
        
        // Buat sesi login
        $_SESSION['status'] = "login";
        $_SESSION['username'] = $username;
        
        // Pindah ke dashboard
        header("Location: dashboard.php");
        exit;
    } else {
        $error = true; // Flag jika password salah
    }

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login dengan Tali Lampu</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* [Kode CSS Kamu Tetap Sama Persis Di Sini, tidak ada yang dikurangi] */
        body { margin: 0; padding: 0; height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: #0a0b0d; font-family: sans-serif; color: white; overflow: hidden; }
        .lamp-container { position: absolute; top: 0; left: 50%; transform: translateX(-50%); display: flex; flex-direction: column; align-items: center; z-index: 10; }
        .wire { width: 4px; height: 120px; background-color: #333; }
        .lamp-shade { width: 100px; height: 40px; background-color: #2c303a; border-radius: 50px 50px 0 0; position: relative; }
        .bulb { width: 50px; height: 50px; background-color: #222; border-radius: 50%; position: absolute; bottom: -25px; left: 25px; z-index: -1; transition: all 0.3s ease; }
        .pull-string { position: absolute; right: 15px; top: 20px; width: 3px; height: 100px; background-color: #777; cursor: pointer; transform-origin: top; transition: transform 0.1s; }
        .pull-string::after { content: ''; position: absolute; bottom: -10px; left: -4.5px; width: 12px; height: 12px; background-color: #ff9800; border-radius: 50%; }
        .pull-string:active { transform: scaleY(1.3); }
        body[data-on="true"] .bulb { background-color: #ffe600; box-shadow: 0 0 80px 30px rgba(206, 187, 14, 0.4), 0 0 200px 80px rgba(189, 171, 40, 0.2); }
        .login-form { background-color: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); padding: 40px; border-radius: 15px; text-align: center; width: 300px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid rgba(255, 255, 255, 0.1); opacity: 0; transform: translateY(50px); pointer-events: none; margin-top: 100px; }
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

    <form class="login-form" method="POST" action="">
        <h2 style="margin-top: 0;">Selamat Datang</h2>
        
        <?php if(isset($error)) : ?>
            <span class="error-msg">Username atau Password salah!</span>
        <?php endif; ?>

        <input type="text" name="username" class="username-input" placeholder="Username" required>
        
        <div class="input-group">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <i class="fa-solid fa-eye" id="togglePassword"></i>
        </div>

        <button type="submit" name="login">Masuk</button>
    </form>

    <script>
        // [Script Javascript kamu tetap sama persis di sini]
        let ison = false;
        const body = document.body;
        const loginForm = document.querySelector('.login-form');
        
        function toggleLamp() {
            ison = !ison; 
            body.setAttribute('data-on', ison);

            if (ison) {
                gsap.to(body, { backgroundColor: "#1c60d6", duration: 0.4 });
                gsap.to(loginForm, { 
                    opacity: 1, y: 0, duration: 0.6, ease: "back.out(1.7)", pointerEvents: "auto", delay: 0.1
                });
            } else {
                gsap.to(body, { backgroundColor: "#0a0b0d", duration: 0.4 });
                gsap.to(loginForm, { 
                    opacity: 0, y: 50, duration: 0.4, ease: "power2.in", pointerEvents: "none" 
                });
            }
        }

        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Script tambahan: Biarkan lampu otomatis menyala jika ada error login
        <?php if(isset($error)) : ?>
            toggleLamp();
        <?php endif; ?>
    </script>
</body>
</html>