<?php
require_once 'includes/init.php';

// Jika sudah login, arahkan ke dashboard masing-masing
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/index.php");
    } else {
        header("Location: user/index.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        // Ambil user dari database
        $stmt = $pdo->prepare("SELECT id, name, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login sukses
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Prevent session fixation
            session_regenerate_id(true);

            if ($user['role'] === 'ADMIN') {
                header("Location: admin/index.php");
            } else {
                header("Location: user/index.php");
            }
            exit;
        } else {
            $error = 'Email atau password salah!';
        }
    } else {
        $error = 'Silakan isi email dan password!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIPERUK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-header h1 {
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        .error-msg {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: var(--radius-md);
            margin-bottom: 16px;
            font-size: 14px;
            border: 1px solid #f87171;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="glass-card login-card">
        <div class="login-header">
            <img src="assets/img/logo.png" alt="SIPERUK Logo" style="height: 240px; margin: -80px 0 -50px 0; object-fit: contain;">
            <p>Sistem Informasi Peminjaman Ruangan Kampus</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="email">Email Kampus</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="email@kampus.ac.id" value="<?php echo h($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" for="password">Kata Sandi</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••" style="padding-right: 40px;">
                    <span id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted);">
                        <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
        
        <div style="text-align: center; margin-top: 24px; font-size: 13px; color: var(--text-muted);">
            Lupa kata sandi? Silakan hubungi bagian <b>IT Support Sarpras</b> untuk melakukan reset akun.
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const password = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    if (password.type === 'password') {
        password.type = 'text';
        // Eye-off icon
        eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        password.type = 'password';
        // Eye icon
        eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
});
</script>

</body>
</html>
