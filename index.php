<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway — Main App Router
 */

require_once __DIR__ . '/config/config.php';
auth_require_login();

$page = $_GET['page'] ?? 'dashboard';

switch ($page) {
    case 'tunnels':
        $sub = $_GET['action'] ?? 'list';
        if ($sub === 'add') {
            require_once __DIR__ . '/pages/tunnels/add.php';
        } elseif ($sub === 'script') {
            require_once __DIR__ . '/pages/tunnels/script.php';
        } else {
            require_once __DIR__ . '/pages/tunnels/list.php';
        }
        break;

    case 'ports':
        $sub = $_GET['action'] ?? 'list';
        if ($sub === 'add') {
            require_once __DIR__ . '/pages/ports/add.php';
        } else {
            require_once __DIR__ . '/pages/ports/list.php';
        }
        break;

    case 'servers':
        auth_require_admin();
        require_once __DIR__ . '/pages/servers/list.php';
        break;

    case 'users':
        auth_require_admin();
        require_once __DIR__ . '/pages/users/list.php';
        break;

    case 'monitoring':
        require_once __DIR__ . '/pages/monitoring/router.php';
        break;

    case 'dashboard':
    default:
        require_once __DIR__ . '/pages/dashboard.php';
        break;
}

