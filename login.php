<?php
/**
 * S.NET VPN Remote — Login Page
 */

require_once __DIR__ . '/config/config.php';

if (auth_check()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $user = db_fetch_one("SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1", 's', [$username]);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['vpn_user'] = [
                'id'        => $user['id'],
                'username'  => $user['username'],
                'full_name' => $user['full_name'],
                'role'      => $user['role'],
                'email'     => $user['email']
            ];
            header("Location: index.php");
            exit;
        } else {
            $error = 'Username atau password yang Anda masukkan salah.';
        }
    } else {
        $error = 'Silakan isi username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #0b1329;
            color: #f8fafc;
            font-family: system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background: #16223f;
            border: 1px solid #233560;
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }
        .header-top {
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);
            padding: 2.2rem 1.8rem 1.8rem;
            text-align: center;
        }
        .form-control {
            background-color: #0d172e;
            border: 1px solid #283a66;
            color: #f1f5f9;
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            background-color: #0d172e;
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.2);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="header-top">
        <div class="d-inline-flex align-items-center justify-content-center bg-white bg-opacity-25 rounded-circle p-3 mb-2 shadow">
            <i class="fa-solid fa-network-wired text-white fs-3"></i>
        </div>
        <h3 class="fw-bold text-white mb-0">S.NET TUNNEL</h3>
        <p class="opacity-75 small mb-0">Mikrotik Remote & DST-NAT Gateway</p>
    </div>

    <div class="p-4 p-md-4">
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 rounded-3 small py-2 px-3 mb-3 d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label small text-muted fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus autocomplete="username">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small text-muted fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-50 text-secondary"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold rounded-3 shadow">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk ke Dashboard
            </button>
        </form>
    </div>

    <div class="p-3 bg-dark bg-opacity-50 text-center text-muted small border-top border-secondary border-opacity-25">
        &copy; <?= date('Y') ?> S.NET Manager (Full PHP 8.x)
    </div>
</div>

</body>
</html>
