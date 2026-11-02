<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway — Easy Web Installer
 * 
 * Skrip instalasi interaktif 100% Pure Full PHP:
 * - Pemeriksaan dependensi & ekstensi PHP
 * - Uji koneksi dan pembuatan tabel database MySQL otomatis
 * - Pembuatan akun Super Admin
 * - Konfigurasi Server Gateway & Pool Subnet VPN
 * - Penguncian instalasi otomatis (.installed)
 */

define('IN_INSTALLER', true);
$baseDir = __DIR__;
$lockFile = $baseDir . '/config/.installed';
$configFile = $baseDir . '/config/db_local.php';

// Jika sudah terinstal, cegah akses ulang demi keamanan
if (file_exists($lockFile)) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Instalasi Selesai — VPN Tunnel & Remote RouterOS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <style>
            body { background: #0f172a; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        </style>
    </head>
    <body>
        <div class="container" style="max-width: 520px;">
            <div class="card p-4 text-center">
                <div class="mb-3">
                    <i class="fa-solid fa-circle-check text-success display-3"></i>
                </div>
                <h3 class="fw-bold mb-2">Aplikasi Sudah Terpasang</h3>
                <p class="text-secondary small mb-4">
                    Sistem VPN Tunnel & Remote RouterOS Gateway telah dikonfigurasi sebelumnya. File instalasi dikunci demi keamanan.
                </p>
                <div class="d-grid gap-2">
                    <a href="login.php" class="btn btn-primary btn-lg fw-semibold">
                        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Masuk ke Dashboard
                    </a>
                </div>
                <div class="mt-4 pt-3 border-top border-secondary small text-muted">
                    Ingin instal ulang? Hapus file pengunci di terminal VPS:<br>
                    <code class="text-warning">rm config/.installed config/db_local.php</code>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$errors = [];
$successMsg = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// 1. Requirement checks
$configWritable = is_writable($baseDir . '/config') || @chmod($baseDir . '/config', 0775) && is_writable($baseDir . '/config');
$reqs = [
    'PHP Version >= 7.4 (Rekomendasi 8.0 - 8.3)' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'Ekstensi MySQLi (Database Driver)'            => extension_loaded('mysqli'),
    'Ekstensi cURL (Komunikasi HTTP)'              => extension_loaded('curl'),
    'Ekstensi JSON (Serialisasi Data)'             => extension_loaded('json'),
    'Ekstensi Sockets (RouterOS API Binary)'       => extension_loaded('sockets'),
    'Direktori config/ dapat ditulis (Writable)'   => $configWritable,
];
$allReqPassed = !in_array(false, $reqs, true);

// Proses form instalasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'test_db') {
        header('Content-Type: application/json');
        $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
        $dbPort = (int)($_POST['db_port'] ?? 3306);
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        $dbName = trim($_POST['db_name'] ?? '');

        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = @new mysqli($dbHost, $dbUser, $dbPass, '', $dbPort);
            $conn->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn->select_db($dbName);
            echo json_encode(['success' => true, 'message' => "Koneksi ke database '{$dbName}' berhasil!"]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => "Koneksi gagal: " . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'do_install') {
        $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
        $dbPort = (int)($_POST['db_port'] ?? 3306);
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        $dbName = trim($_POST['db_name'] ?? '');

        $adminUser  = trim($_POST['admin_user'] ?? 'admin');
        $adminPass  = $_POST['admin_pass'] ?? '';
        $adminName  = trim($_POST['admin_name'] ?? 'Super Administrator');
        $adminEmail = trim($_POST['admin_email'] ?? 'admin@vpn-tunnel.net');

        $serverName = trim($_POST['server_name'] ?? 'Master Gateway VPN');
        $publicIp   = trim($_POST['public_ip'] ?? '');
        $vpnSubnet  = trim($_POST['vpn_subnet'] ?? '10.88.0.0/16');
        $vpnProto   = trim($_POST['vpn_protocol'] ?? 'wireguard');
        $minPort    = (int)($_POST['min_port'] ?? 10000);
        $maxPort    = (int)($_POST['max_port'] ?? 50000);

        if (!$dbUser || !$dbName || !$adminUser || !$adminPass || !$publicIp) {
            $errors[] = "Semua field bertanda bintang (*) wajib diisi!";
        } else {
            try {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                $conn = new mysqli($dbHost, $dbUser, $dbPass, '', $dbPort);
                $conn->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $conn->select_db($dbName);
                $conn->set_charset('utf8mb4');

                // 1. Buat Tabel Users
                $conn->query("CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `username` VARCHAR(64) NOT NULL UNIQUE,
                    `password` VARCHAR(255) NOT NULL,
                    `role` ENUM('admin', 'user') DEFAULT 'admin',
                    `full_name` VARCHAR(120) NOT NULL,
                    `email` VARCHAR(120) DEFAULT NULL,
                    `phone` VARCHAR(30) DEFAULT NULL,
                    `status` ENUM('active', 'suspended') DEFAULT 'active',
                    `max_tunnels` INT DEFAULT 20,
                    `max_ports` INT DEFAULT 50,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // 2. Buat Tabel Servers (Gateway IP Publik)
                $conn->query("CREATE TABLE IF NOT EXISTS `servers` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `server_name` VARCHAR(100) NOT NULL,
                    `public_ip` VARCHAR(50) NOT NULL,
                    `vpn_subnet` VARCHAR(50) DEFAULT '10.88.0.0/16',
                    `vpn_interface` VARCHAR(50) DEFAULT 'wg-tunnel',
                    `vpn_protocol` ENUM('wireguard', 'sstp', 'l2tp', 'all') DEFAULT 'wireguard',
                    `engine_type` ENUM('linux_iptables', 'mikrotik_chr') DEFAULT 'linux_iptables',
                    `chr_api_host` VARCHAR(50) DEFAULT NULL,
                    `chr_api_user` VARCHAR(64) DEFAULT NULL,
                    `chr_api_password` VARCHAR(255) DEFAULT NULL,
                    `chr_api_port` INT DEFAULT 8728,
                    `min_port` INT DEFAULT 10000,
                    `max_port` INT DEFAULT 50000,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // 3. Buat Tabel Tunnels (Client Router Mikrotik)
                $conn->query("CREATE TABLE IF NOT EXISTS `tunnels` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `server_id` INT NOT NULL,
                    `client_name` VARCHAR(100) NOT NULL,
                    `vpn_username` VARCHAR(64) NOT NULL UNIQUE,
                    `vpn_password` VARCHAR(100) NOT NULL,
                    `tunnel_ip` VARCHAR(50) NOT NULL UNIQUE,
                    `protocol` ENUM('wireguard', 'sstp', 'l2tp') DEFAULT 'wireguard',
                    `wireguard_private_key` VARCHAR(100) DEFAULT NULL,
                    `wireguard_public_key` VARCHAR(100) DEFAULT NULL,
                    `wireguard_preshared_key` VARCHAR(100) DEFAULT NULL,
                    `api_user` VARCHAR(64) DEFAULT 'admin',
                    `api_password` VARCHAR(255) DEFAULT NULL,
                    `api_port` INT DEFAULT 8728,
                    `status` ENUM('online', 'offline') DEFAULT 'offline',
                    `last_seen` DATETIME DEFAULT NULL,
                    `last_latency` INT DEFAULT NULL,
                    `rx_bytes` BIGINT DEFAULT 0,
                    `tx_bytes` BIGINT DEFAULT 0,
                    `router_model` VARCHAR(100) DEFAULT NULL,
                    `routeros_version` VARCHAR(50) DEFAULT NULL,
                    `router_cpu` INT DEFAULT NULL,
                    `router_uptime` VARCHAR(100) DEFAULT NULL,
                    `expires_at` DATETIME DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (`user_id`),
                    INDEX (`server_id`),
                    INDEX (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // 4. Buat Tabel Port Forwards (DST-NAT Mapping)
                $conn->query("CREATE TABLE IF NOT EXISTS `port_forwards` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `tunnel_id` INT NOT NULL,
                    `label` VARCHAR(100) NOT NULL,
                    `public_port` INT NOT NULL UNIQUE,
                    `target_ip` VARCHAR(50) NOT NULL,
                    `target_port` INT NOT NULL,
                    `protocol` ENUM('tcp', 'udp', 'both') DEFAULT 'tcp',
                    `is_enabled` TINYINT(1) DEFAULT 1,
                    `notes` TEXT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (`tunnel_id`),
                    INDEX (`public_port`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // 5. Buat Tabel Settings
                $conn->query("CREATE TABLE IF NOT EXISTS `settings` (
                    `key` VARCHAR(100) PRIMARY KEY,
                    `value` TEXT NOT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // 6. Buat Tabel Logs
                $conn->query("CREATE TABLE IF NOT EXISTS `tunnel_logs` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `tunnel_id` INT DEFAULT NULL,
                    `event` VARCHAR(50) NOT NULL,
                    `details` TEXT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // Simpan default Super Admin
                $hashedPass = password_hash($adminPass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO `users` (username, password, role, full_name, email, status) VALUES (?, ?, 'admin', ?, ?, 'active') ON DUPLICATE KEY UPDATE password = VALUES(password)");
                $stmt->bind_param('ssss', $adminUser, $hashedPass, $adminName, $adminEmail);
                $stmt->execute();
                $adminId = $stmt->insert_id ?: 1;
                $stmt->close();

                // Simpan default Server IP Publik
                $stmt = $conn->prepare("INSERT INTO `servers` (server_name, public_ip, vpn_subnet, vpn_protocol, min_port, max_port, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->bind_param('ssssii', $serverName, $publicIp, $vpnSubnet, $vpnProto, $minPort, $maxPort);
                $stmt->execute();
                $stmt->close();

                // Simpan pengaturan aplikasi
                $appSettings = [
                    'app_name'           => 'VPN Tunnel & Remote RouterOS',
                    'app_domain'         => $_SERVER['HTTP_HOST'] ?? 'localhost',
                    'default_server_ip'  => $publicIp,
                    'default_vpn_subnet' => $vpnSubnet,
                    'wireguard_port'     => '51820',
                    'sstp_port'          => '443'
                ];
                foreach ($appSettings as $k => $v) {
                    $stmt = $conn->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                    $stmt->bind_param('ss', $k, $v);
                    $stmt->execute();
                    $stmt->close();
                }

                // Buat file konfigurasi database lokal (config/db_local.php)
                $dbConfigContent = "<?php\n"
                    . "// Dibuat otomatis oleh VPN Tunnel Installer pada " . date('Y-m-d H:i:s') . "\n"
                    . "define('DB_HOST', " . var_export($dbHost, true) . ");\n"
                    . "define('DB_USER', " . var_export($dbUser, true) . ");\n"
                    . "define('DB_PASS', " . var_export($dbPass, true) . ");\n"
                    . "define('DB_NAME', " . var_export($dbName, true) . ");\n"
                    . "define('DB_PORT', " . (int)$dbPort . ");\n"
                    . "define('DB_CHARSET', 'utf8mb4');\n";

                @chmod($baseDir . '/config', 0777);
                $w1 = @file_put_contents($configFile, $dbConfigContent);
                $w2 = @file_put_contents($lockFile, date('Y-m-d H:i:s'));

                if ($w1 === false || $w2 === false || !file_exists($lockFile)) {
                    $errors[] = "Gagal menulis file konfigurasi ke folder 'config/' karena terhalang izin kepemilikan Linux (Permission Denied).<br><br><strong>Cara Mengatasi:</strong> Jalankan perintah ini di Terminal VPS:<br><pre class='p-2 my-2 bg-dark rounded text-info'>chown -R www:www " . htmlspecialchars($baseDir) . "\nchmod -R 775 " . htmlspecialchars($baseDir . '/config') . "</pre>Setelah itu, klik tombol <strong>'Pasang & Selesaikan Instalasi'</strong> sekali lagi.";
                } else {
                    header("Location: install.php?step=success");
                    exit;
                }

            } catch (Throwable $e) {
                $errors[] = "Gagal memproses instalasi: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Installer — VPN Tunnel & Remote RouterOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg-color: #0b1329;
            --card-bg: #16223f;
            --card-border: #233560;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
        }
        body {
            background-color: var(--bg-color);
            color: #e2e8f0;
            font-family: system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .installer-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            width: 100%;
            max-width: 780px;
            overflow: hidden;
        }
        .header-gradient {
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);
            padding: 2.2rem 2rem 1.8rem;
            color: #ffffff;
        }
        .form-control, .form-select {
            background-color: #0d172e;
            border: 1px solid #283a66;
            color: #f1f5f9;
            border-radius: 10px;
            padding: 0.65rem 0.9rem;
        }
        .form-control:focus, .form-select:focus {
            background-color: #0d172e;
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.2);
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 0.35rem;
        }
        .step-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            color: #fff;
            font-weight: 700;
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }
        .step-pill.active {
            background: #ffffff;
            color: #1e3a8a;
        }
        .nav-tabs .nav-link {
            color: #94a3b8;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 0.75rem 1rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .nav-tabs .nav-link.active {
            color: #38bdf8;
            background: transparent;
            border-bottom-color: #38bdf8;
        }
        .code-pill {
            background: #091024;
            padding: 0.15rem 0.45rem;
            border-radius: 5px;
            color: #38bdf8;
            font-family: monospace;
        }
    </style>
</head>
<body>

<div class="installer-card">
    <div class="header-gradient text-center">
        <div class="d-inline-flex align-items-center justify-content-center bg-white bg-opacity-25 rounded-circle p-3 mb-3 shadow">
            <i class="fa-solid fa-network-wired text-white fs-2"></i>
        </div>
        <h2 class="fw-bold mb-1">VPN Tunnel & Remote RouterOS Gateway</h2>
        <p class="mb-0 opacity-75 small">Instalasi Cepat Web-Based Full Pure PHP (DST-NAT Port Forwarding & Mikrotik API)</p>
    </div>

    <div class="p-4 p-md-5">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4">
                <div class="d-flex align-items-center mb-1">
                    <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                    <strong class="fs-6">Terjadi Kesalahan:</strong>
                </div>
                <ul class="mb-0 small ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($step === 99 || (isset($_GET['step']) && $_GET['step'] === 'success')): ?>
            <!-- STEP SUKSES -->
            <div class="text-center py-4">
                <div class="mb-3">
                    <i class="fa-solid fa-circle-check text-success display-1"></i>
                </div>
                <h3 class="fw-bold text-white mb-2">Instalasi Berhasil Sempurna!</h3>
                <p class="text-secondary small mb-4" style="max-width: 500px; margin: 0 auto;">
                    Seluruh tabel database, akun administrator, serta gateway VPN & DST-NAT telah siap digunakan. File konfigurasi telah dikunci.
                </p>

                <div class="card bg-dark bg-opacity-50 border-secondary border-opacity-25 text-start p-3 mb-4 mx-auto" style="max-width: 480px;">
                    <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                        <span class="text-muted small">URL Dashboard:</span>
                        <span class="fw-semibold small text-info"><a href="index.php" class="text-info text-decoration-none">http://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/</a></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                        <span class="text-muted small">Username Login:</span>
                        <span class="fw-semibold small text-white">admin</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted small">File Konfigurasi:</span>
                        <span class="code-pill small">config/db_local.php</span>
                    </div>
                </div>

                <div class="d-grid gap-2 col-md-6 mx-auto">
                    <a href="login.php" class="btn btn-primary btn-lg fw-semibold shadow">
                        <i class="fa-solid fa-right-to-bracket me-2"></i> Buka Halaman Login
                    </a>
                </div>
            </div>

        <?php else: ?>

            <!-- STATUS REQUIREMENTS -->
            <div class="mb-4 p-3 rounded-3" style="background: #0d172e; border: 1px solid #1e2c4f;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small text-white text-uppercase tracking-wider">
                        <i class="fa-solid fa-list-check text-info me-2"></i> Pemeriksaan Kesiapan Server
                    </span>
                    <span class="badge <?= $allReqPassed ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= $allReqPassed ? 'Semua Persyaratan Terpenuhi' : 'Periksa Item Tertunda' ?>
                    </span>
                </div>
                <div class="row g-2">
                    <?php foreach ($reqs as $label => $pass): ?>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center small text-muted">
                                <i class="fa-solid <?= $pass ? 'fa-check text-success' : 'fa-xmark text-danger' ?> me-2"></i>
                                <span class="<?= $pass ? 'text-secondary' : 'text-danger fw-bold' ?>"><?= $label ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- FORM INSTALASI -->
            <form method="POST" action="install.php" id="installForm">
                <input type="hidden" name="action" value="do_install">

                <ul class="nav nav-tabs mb-4 border-secondary border-opacity-25" id="installTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="tab-db-btn" data-bs-toggle="tab" data-bs-target="#tab-db" type="button">
                            <span class="step-pill">1</span> Database MySQL
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-vpn-btn" data-bs-toggle="tab" data-bs-target="#tab-vpn" type="button">
                            <span class="step-pill">2</span> Gateway & IP Publik
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-admin-btn" data-bs-toggle="tab" data-bs-target="#tab-admin" type="button">
                            <span class="step-pill">3</span> Akun Super Admin
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- TAB 1: DATABASE -->
                    <div class="tab-pane fade show active" id="tab-db">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Database Host *</label>
                                <input type="text" name="db_host" id="db_host" class="form-control" value="127.0.0.1" required>
                                <div class="form-text text-secondary small">Gunakan <code class="code-pill">127.0.0.1</code> atau <code class="code-pill">localhost</code> jika database berada di VPS yang sama.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Database Port *</label>
                                <input type="number" name="db_port" id="db_port" class="form-control" value="3306" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Database (DB Name) *</label>
                                <input type="text" name="db_name" id="db_name" class="form-control" value="vpn_tunnel" required>
                                <div class="form-text text-secondary small">Jika belum ada, installer akan membuatkannya secara otomatis.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Database User *</label>
                                <input type="text" name="db_user" id="db_user" class="form-control" value="root" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Database Password</label>
                                <input type="password" name="db_pass" id="db_pass" class="form-control" placeholder="Kosongkan jika tidak ada password">
                            </div>
                            <div class="col-12 mt-3">
                                <button type="button" class="btn btn-outline-info btn-sm" id="btnTestDb">
                                    <i class="fa-solid fa-plug me-1"></i> Uji Koneksi Database
                                </button>
                                <span id="testDbResult" class="ms-2 small"></span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-end">
                            <button type="button" class="btn btn-primary" onclick="switchTab('tab-vpn-btn')">
                                Lanjut: Konfigurasi Server Gateway <i class="fa-solid fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: SERVER GATEWAY -->
                    <div class="tab-pane fade" id="tab-vpn">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Gateway Server *</label>
                                <input type="text" name="server_name" class="form-control" value="VPS Cloud Gateway 1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">IP Publik VPS Gateway *</label>
                                <input type="text" name="public_ip" class="form-control" placeholder="Contoh: 103.186.x.x" value="<?= htmlspecialchars($_SERVER['SERVER_ADDR'] ?? '') ?>" required>
                                <div class="form-text text-secondary small">IP Publik yang akan dihubungi oleh router Mikrotik klien dan diakses via Winbox.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subnet Alokasi IP Tunnel (Pool) *</label>
                                <input type="text" name="vpn_subnet" class="form-control" value="10.88.0.0/16" required>
                                <div class="form-text text-secondary small">Setiap router klien akan mendapatkan IP statis otomatis dari subnet ini (misal 10.88.1.2).</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Protokol VPN Utama</label>
                                <select name="vpn_protocol" class="form-select">
                                    <option value="wireguard" selected>WireGuard (Rekomendasi - RouterOS v7 Kencang & Hemat RAM)</option>
                                    <option value="sstp">SSTP (Kompatibel RouterOS v6 & v7 via Port 443 TCP)</option>
                                    <option value="all">Mendukung Keduanya (WireGuard & SSTP)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rentang Port Publik DST-NAT (Mulai)</label>
                                <input type="number" name="min_port" class="form-control" value="10000" min="1024" max="65535">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rentang Port Publik DST-NAT (Sampai)</label>
                                <input type="number" name="max_port" class="form-control" value="50000" min="1024" max="65535">
                                <div class="form-text text-secondary small">Port forward publik (misal Winbox: 18291) akan dialokasikan di dalam rentang ini.</div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="switchTab('tab-db-btn')">
                                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                            </button>
                            <button type="button" class="btn btn-primary" onclick="switchTab('tab-admin-btn')">
                                Lanjut: Akun Admin <i class="fa-solid fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 3: SUPER ADMIN -->
                    <div class="tab-pane fade" id="tab-admin">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap Administrator *</label>
                                <input type="text" name="admin_name" class="form-control" value="Super Administrator" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Kontak *</label>
                                <input type="email" name="admin_email" class="form-control" value="admin@vpn-tunnel.net" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username Login Dashboard *</label>
                                <input type="text" name="admin_user" class="form-control" value="admin" required autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password Login *</label>
                                <input type="password" name="admin_pass" class="form-control" placeholder="Buat password yang kuat" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-secondary" onclick="switchTab('tab-vpn-btn')">
                                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
                            </button>
                            <button type="submit" class="btn btn-success btn-lg fw-semibold px-4 shadow">
                                <i class="fa-solid fa-check me-2"></i> Pasang & Selesaikan Instalasi
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        <?php endif; ?>

    </div>

    <div class="p-3 bg-dark bg-opacity-75 text-center text-muted small border-top border-secondary border-opacity-25">
        &copy; <?= date('Y') ?> VPN Tunnel Manager — High-Performance Mikrotik Remote & VPN Tunnel Engine (100% Pure PHP)
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchTab(tabId) {
    const triggerEl = document.getElementById(tabId);
    if (triggerEl) {
        bootstrap.Tab.getOrCreateInstance(triggerEl).show();
    }
}

document.getElementById('btnTestDb')?.addEventListener('click', function() {
    const btn = this;
    const resultSpan = document.getElementById('testDbResult');
    const formData = new FormData();
    formData.append('action', 'test_db');
    formData.append('db_host', document.getElementById('db_host').value);
    formData.append('db_port', document.getElementById('db_port').value);
    formData.append('db_user', document.getElementById('db_user').value);
    formData.append('db_pass', document.getElementById('db_pass').value);
    formData.append('db_name', document.getElementById('db_name').value);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menguji...';
    resultSpan.className = 'ms-2 small text-muted';
    resultSpan.textContent = '';

    fetch('install.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-plug me-1"></i> Uji Koneksi Database';
        if (data.success) {
            resultSpan.className = 'ms-2 small text-success fw-bold';
            resultSpan.innerHTML = '<i class="fa-solid fa-check me-1"></i> ' + data.message;
        } else {
            resultSpan.className = 'ms-2 small text-danger fw-bold';
            resultSpan.innerHTML = '<i class="fa-solid fa-xmark me-1"></i> ' + data.message;
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-plug me-1"></i> Uji Koneksi Database';
        resultSpan.className = 'ms-2 small text-danger';
        resultSpan.textContent = 'Gagal menghubungi server instalasi: ' + err.message;
    });
});
</script>
</body>
</html>

