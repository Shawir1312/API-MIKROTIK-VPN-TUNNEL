<?php
/**
 * S.NET VPN TUNNEL & REMOTE ROUTEROS
 * Process Controller: Server Action (Gateway CRUD)
 */

define('IN_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_require_admin();

$action = post('action', get('action', ''));

switch ($action) {
    case 'create':
        $name         = trim(post('name', ''));
        $publicIp     = trim(post('public_ip', ''));
        $gatewayType  = trim(post('gateway_type', 'linux_iptables'));
        $tunnelSubnet = trim(post('tunnel_subnet', '10.88.0.0/24'));
        $minPort      = (int)post('min_port', 10000);
        $maxPort      = (int)post('max_port', 50000);
        $wgPort       = (int)post('wg_port', 51820);
        $wgInterface  = trim(post('wg_interface', 'wg0'));
        $apiPort      = (int)post('api_port', 8728);
        $apiUser      = trim(post('api_user', ''));
        $apiPass      = trim(post('api_pass', ''));

        if (!$name || !$publicIp) {
            flash_set('danger', 'Nama Server dan IP Publik wajib diisi.');
            header("Location: ../index.php?page=servers");
            exit;
        }

        db_insert(
            "INSERT INTO servers (name, public_ip, gateway_type, tunnel_subnet, min_port, max_port, wg_port, wg_interface, api_port, api_user, api_pass, is_active) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
            'ssssiiisis',
            [$name, $publicIp, $gatewayType, $tunnelSubnet, $minPort, $maxPort, $wgPort, $wgInterface, $apiPort, $apiUser, $apiPass]
        );

        flash_set('success', "Gateway Server '{$name}' ({$publicIp}) berhasil ditambahkan!");
        header("Location: ../index.php?page=servers");
        exit;

    case 'delete':
        $id = (int)post('id', get('id', 0));
        $server = db_fetch_one("SELECT * FROM servers WHERE id = ?", 'i', [$id]);
        if (!$server) {
            flash_set('danger', 'Server tidak ditemukan.');
            header("Location: ../index.php?page=servers");
            exit;
        }

        // Hapus ports dan tunnels terkait
        db_execute("DELETE FROM ports WHERE server_id = ?", 'i', [$id]);
        db_execute("DELETE FROM tunnels WHERE server_id = ?", 'i', [$id]);
        db_execute("DELETE FROM servers WHERE id = ?", 'i', [$id]);

        flash_set('success', "Gateway Server '{$server['name']}' berhasil dihapus.");
        header("Location: ../index.php?page=servers");
        exit;

    default:
        header("Location: ../index.php?page=servers");
        exit;
}
