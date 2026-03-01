<?php
header("Location: login.html");
exit;
session_start();

// Jika user sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['status_login']) && $_SESSION['status_login'] == true) {
    header("Location: dashboard.php");
    exit;
}

$error = false;
// Cek apakah tombol login ditekan
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cek kecocokan username dan password (Silakan sesuaikan dengan database Anda nanti)
    if ($username === 'admin' && $password === 'admin123') {
        // Buat session
        $_SESSION['status_login'] = true;
        $_SESSION['username'] = $username;
        
        // Arahkan ke dashboard
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
    <title>Login Budi Homestay</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <style>
        /* ... (Masukkan semua kode CSS lampu dan form Anda di sini) ... */
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
        .login-form input { display: block; width: 100%; margin: 15px 0; padding: 12px; border-radius: 8px; border: none; background-color: rgba(255, 255, 255, 0.9); box-sizing: border-box; outline: none; }
        .login-form button { width: 100%; padding: 12px; background-color: #ff9800; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .login-form button:hover { background-color: #e68a00; }
        /* Error text */
        .error-msg { color: #ff5252; font-size: 14px; margin-bottom: 10px; }
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
        
        <?php if($error): ?>
            <div class="error-msg">Username atau Password salah!</div>
        <?php endif; ?>

        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <button type="submit" name="login">Masuk</button>
    </form>

    <script>
        let ison = false;
        const body = document.body;
        const loginForm = document.querySelector('.login-form');
        
        // Jika terjadi error login, biarkan lampu tetap menyala
        <?php if($error): ?>
            ison = true;
            body.setAttribute('data-on', ison);
            body.style.backgroundColor = "#1c1f24";
            loginForm.style.opacity = 1;
            loginForm.style.transform = "translateY(0)";
            loginForm.style.pointerEvents = "auto";
        <?php endif; ?>

        function toggleLamp() {
            ison = !ison; 
            body.setAttribute('data-on', ison);

            if (ison) {
                gsap.to(body, { backgroundColor: "#1c1f24", duration: 0.4 });
                gsap.to(loginForm, { opacity: 1, y: 0, duration: 0.6, ease: "back.out(1.7)", pointerEvents: "auto", delay: 0.1 });
            } else {
                gsap.to(body, { backgroundColor: "#0a0b0d", duration: 0.4 });
                gsap.to(loginForm, { opacity: 0, y: 50, duration: 0.4, ease: "power2.in", pointerEvents: "none" });
            }
        }
    </script>
</body>
</html>