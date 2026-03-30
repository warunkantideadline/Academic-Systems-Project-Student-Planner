<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth.php';

// Kalau sudah login, redirect ke home
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        // Baca users.json langsung dari BASE_DATA_PATH
        $usersFile = DATA_PATH . 'users.json';
        $users     = [];
        if (file_exists($usersFile)) {
            $decoded = json_decode(file_get_contents($usersFile), true);
            $users   = is_array($decoded) ? $decoded : [];
        }

        $userFound = null;
        foreach ($users as $u) {
            if ($u['username'] === $username) {
                $userFound = $u;
                break;
            }
        }

        if ($userFound && password_verify($password, $userFound['password'])) {
            loginUser($userFound);
            // Buat folder data user otomatis
            getUserDataPath();
            header('Location: index.php');
            exit();
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SiAkad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --primary-dark: #3730a3; }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e1b4b 0%, #4f46e5 50%, #7c3aed 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            animation: fadeInUp 0.4s ease both;
        }

        .login-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 150px; height: 150px;
            background: rgba(255,255,255,0.07);
            border-radius: 50%;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -30px; left: -30px;
            width: 100px; height: 100px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .login-logo {
            width: 64px; height: 64px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(8px);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #fff;
            margin: 0 auto 1rem;
            position: relative;
            z-index: 1;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 0.25rem;
            position: relative;
            z-index: 1;
        }

        .login-subtitle {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.7);
            position: relative;
            z-index: 1;
        }

        .login-body { padding: 2rem; }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.4rem;
            display: block;
        }

        .input-group-icon { position: relative; }

        .input-group-icon .form-control {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.7rem 2.75rem 0.7rem 0.9rem;
            font-size: 0.9rem;
            color: #1e293b;
            width: 100%;
            transition: all 0.2s;
            outline: none;
        }

        .input-group-icon .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }

        .input-group-icon .input-icon {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            cursor: pointer;
            z-index: 5;
            transition: color 0.2s;
        }

        .input-group-icon .input-icon:hover { color: var(--primary); }

        .btn-login {
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.8rem;
            font-size: 0.95rem;
            font-weight: 700;
            width: 100%;
            transition: all 0.25s;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.5);
            color: #fff;
        }

        .btn-login:active { transform: translateY(0); }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            animation: shake 0.4s ease;
        }

        .default-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.8rem;
            color: #64748b;
        }

        .default-info code {
            background: #e0e7ff;
            color: #4f46e5;
            padding: 0.1em 0.4em;
            border-radius: 4px;
            font-weight: 600;
        }

        .login-footer {
            padding: 1rem 2rem 1.5rem;
            text-align: center;
            font-size: 0.78rem;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%       { transform: translateX(-6px); }
            40%       { transform: translateX(6px); }
            60%       { transform: translateX(-4px); }
            80%       { transform: translateX(4px); }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">

        <!-- HEADER -->
        <div class="login-header">
            <div class="login-logo">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div class="login-title">SiAkad</div>
            <div class="login-subtitle">Sistem Akademik Mahasiswa</div>
        </div>

        <!-- BODY -->
        <div class="login-body">

            <div class="default-info">
                <i class="bi bi-info-circle me-1 text-primary"></i>
                Default login: username <code>admin</code> · password <code>password</code>
            </div>

            <?php if ($error): ?>
            <div class="alert-error">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="off">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group-icon">
                        <input type="text"
                               name="username"
                               class="form-control"
                               placeholder="Masukkan username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               required autofocus>
                        <i class="bi bi-person input-icon"></i>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group-icon">
                        <input type="password"
                               name="password"
                               id="passwordInput"
                               class="form-control"
                               placeholder="Masukkan password"
                               required>
                        <i class="bi bi-eye input-icon" id="togglePassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Masuk ke SiAkad
                </button>

            </form>
        </div>

        <!-- FOOTER -->
        <div class="login-footer">
            <i class="bi bi-shield-lock me-1"></i>
            SiAkad &copy; <?= date('Y') ?> · Sistem Akademik Mahasiswa
        </div>

    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    var input = document.getElementById('passwordInput');
    if (input.type === 'password') {
        input.type = 'text';
        this.className = 'bi bi-eye-slash input-icon';
    } else {
        input.type = 'password';
        this.className = 'bi bi-eye input-icon';
    }
});
</script>
</body>
</html>