<?php
if (!defined('IN_APP')) die('Direct access denied.');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom Dark/Light UI Theme -->
    <style>
        :root {
            --bg-body: #0b1329;
            --bg-card: #16223f;
            --border-card: #233560;
            --accent: #2563eb;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
        }
        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
        }
        .navbar-custom {
            background: #111c35;
            border-bottom: 1px solid var(--border-card);
        }
        .sidebar {
            background: #111c35;
            border-right: 1px solid var(--border-card);
            min-height: calc(100vh - 65px);
        }
        .sidebar .nav-link {
            color: var(--text-sub);
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            border-radius: 8px;
            margin: 0.15rem 0.65rem;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #38bdf8;
            background: #1a294d;
        }
        .sidebar .nav-link i {
            width: 24px;
            font-size: 1.1rem;
            margin-right: 0.5rem;
        }
        .card-custom {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.25);
        }
        .form-control, .form-select {
            background-color: #0d172e;
            border: 1px solid #283a66;
            color: #f1f5f9;
            border-radius: 8px;
        }
        .form-control:focus, .form-select:focus {
            background-color: #0d172e;
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.2);
        }
        .table-custom {
            color: #e2e8f0;
            border-color: var(--border-card);
        }
        .table-custom th {
            background: #0d172e;
            color: #94a3b8;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-card);
        }
        .table-custom td {
            background: transparent;
            border-bottom: 1px solid var(--border-card);
            vertical-align: middle;
        }
        .badge-online { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); }
        .badge-offline { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); }
        .badge-port { background: #1e3a8a; color: #60a5fa; font-family: monospace; font-size: 0.85rem; padding: 0.35rem 0.6rem; border-radius: 6px; }
        .copy-btn { cursor: pointer; transition: 0.2s; }
        .copy-btn:hover { color: #38bdf8; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-3 py-2 sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center fw-bold fs-5 text-white" href="index.php">
            <span class="d-inline-flex align-items-center justify-content-center bg-primary rounded-3 p-2 me-2 shadow-sm" style="width: 36px; height: 36px;">
                <i class="fa-solid fa-network-wired text-white fs-6"></i>
            </span>
            S.NET <span class="text-primary ms-1">TUNNEL</span>
        </a>
        <div class="d-flex align-items-center">
            <span class="badge bg-dark border border-secondary text-info me-3 d-none d-md-inline-block">
                <i class="fa-solid fa-server me-1"></i> <?= htmlspecialchars(get_app_setting('default_server_ip', 'Gateway Active')) ?>
            </span>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle text-light d-flex align-items-center rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-circle-user text-primary me-2 fs-6"></i>
                    <span><?= htmlspecialchars($user['full_name'] ?? 'Administrator') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow border-secondary">
                    <li><h6 class="dropdown-header small text-muted">Role: <?= strtoupper($user['role'] ?? 'USER') ?></h6></li>
                    <li><hr class="dropdown-divider border-secondary"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Keluar</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse p-0" id="sidebarMenu">
            <div class="py-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= (!isset($_GET['page']) || $_GET['page'] === 'dashboard') ? 'active' : '' ?>" href="index.php">
                            <i class="fa-solid fa-gauge-high"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (($_GET['page'] ?? '') === 'tunnels') ? 'active' : '' ?>" href="index.php?page=tunnels">
                            <i class="fa-solid fa-diagram-project"></i> Router Tunnels
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (($_GET['page'] ?? '') === 'ports') ? 'active' : '' ?>" href="index.php?page=ports">
                            <i class="fa-solid fa-shuffle"></i> DST-NAT Forwarding
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (($_GET['page'] ?? '') === 'monitoring') ? 'active' : '' ?>" href="index.php?page=monitoring">
                            <i class="fa-solid fa-chart-line"></i> Monitoring Live
                        </a>
                    </li>
                    <?php if (is_admin()): ?>
                    <li class="nav-item mt-3 px-3">
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.08em;">Administrasi</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (($_GET['page'] ?? '') === 'servers') ? 'active' : '' ?>" href="index.php?page=servers">
                            <i class="fa-solid fa-server"></i> Server Gateway
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (($_GET['page'] ?? '') === 'users') ? 'active' : '' ?>" href="index.php?page=users">
                            <i class="fa-solid fa-users"></i> Kelola Pengguna
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <!-- Main Content Body -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <?php render_flash(); ?>
