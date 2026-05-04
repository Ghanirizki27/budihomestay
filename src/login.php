<?php
session_start();
include "koneksi.php";
include_once "auth.php";

// 1. Cek jika sudah login, arahkan ke halaman yang benar sesuai role-nya
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    redirectByRole();
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
        $_SESSION['role'] = $data['role'];

        if ($data['role'] == 'admin') {
            header("Location: dashboard.php");
        } else if ($data['role'] == 'penyewa') {
            header("Location: home_penyewa.php");
        } else {
            $error = true;
        }
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
    <title>Login - Budi Homestay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff; /* Background Putih Bersih */
        }

        /* CARD LOGIN */
        .login-card {
            background: white;
            padding: 50px 40px;
            border-radius: 24px;
            width: 100%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.05); /* Shadow lebih halus */
        }

        /* BINGKAI LOGO (VIDEO) */
        .logo-container {
            width: 100px;
            height: 100px;
            background: #f0f2f5;
            margin: 0 auto 25px;
            border-radius: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        .logo-container video {
            width: 80%;
            height: 80%;
            object-fit: contain;
            border-radius: 10px;
        }

        /* JUDUL */
        h2.title {
            margin: 0 0 35px;
            color: #0f3c74;
            font-size: 24px;
            font-weight: 800;
        }

        /* INPUT GROUP */
        .input-group {
            position: relative;
            margin-bottom: 18px;
        }
        
        input {
            width: 100%;
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid transparent;
            background: #edf3ff; /* Warna Biru Muda Presisi */
            outline: none;
            font-size: 15px;
            font-family: inherit;
            color: #13355f;
            transition: 0.3s;
        }

        input:focus {
            background: #e1ebff;
            border-color: #2f80ed;
        }

        /* ICON MATA */
        .input-group i {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #637892;
            cursor: pointer;
            font-size: 18px;
        }

        /* TOMBOL MASUK */
        button {
            width: 100%;
            padding: 16px;
            background: #3a8ef6; /* Biru Solid Presisi */
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 8px 20px rgba(58, 142, 246, 0.25);
        }

        button:hover {
            background: #2f80ed;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(58, 142, 246, 0.35);
        }

        /* PESAN ERROR */
        .error-msg {
            background: #fff1f1;
            color: #eb5757;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid #ffdbdb;
        }
    </style>
</head>

<body>

<div class="login-card">
    <!-- LOGO CONTAINER -->
    <div class="logo-container">
        <video autoplay loop muted playsinline>
            <source src="Budi.mp4" type="video/mp4">
        </video>
    </div>

    <h2 class="title">Budi Homestay</h2>

    <?php if($error): ?>
        <div class="error-msg">
            <i class="fa-solid fa-circle-exclamation"></i> Username atau Password salah
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <input type="text" name="username" placeholder="Username" required autocomplete="off">
        </div>

        <div class="input-group">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <i class="fa-solid fa-eye" id="togglePassword"></i>
        </div>

        <button type="submit" name="login">Masuk</button>
    </form>
</div>

<script>
    // Toggle password visibility
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