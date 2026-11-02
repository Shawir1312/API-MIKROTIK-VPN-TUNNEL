<?php
/**
 * VPN Tunnel & Remote RouterOS — Process Tunnel Actions
 */

require_once dirname(__DIR__) . '/config/config.php';
auth_require_login();

$user = current_user();
$userId = (int)$user['id'];
$isAdmin = is_admin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientName  = trim($_POST['client_name'] ?? '');
    $serverId    = (int)($_POST['server_id'] ?? 0);
    $protocol    = trim($_POST['protocol'] ?? 'wireguard');
    $tunnelIp    = trim($_POST['tunnel_ip'] ?? '');
    $vpnUsername = trim($_POST['vpn_username'] ?? '');
    $vpnPassword = trim($_POST['vpn_password'] ?? '');
    $apiUser     = trim($_POST['api_user'] ?? 'admin');
    $apiPassword = $_POST['api_password'] ?? '';
    $apiPort     = (int)($_POST['api_port'] ?? 8728);

    if (!$clientName || !$serverId || !$tunnelIp || !$vpnUsername || !$vpnPassword) {
        flash_set('danger', 'Semua field bertanda bintang wajib diisi.');
        header("Location: ../index.php?page=tunnels&action=add");
        exit;
    }

    // Cek apakah username VPN atau IP tunnel sudah ada
    $exists = db_fetch_one("SELECT id FROM tunnels WHERE vpn_username = ? OR tunnel_ip = ?", 'ss', [$vpnUsername, $tunnelIp]);
    if ($exists) {
        flash_set('danger', 'Username VPN atau IP Tunnel sudah terpakai. Silakan gunakan nilai lain.');
        header("Location: ../index.php?page=tunnels&action=add");
        exit;
    }

    // Generate keys jika WireGuard
    $wgPriv = '';
    $wgPub  = '';
    if ($protocol === 'wireguard') {
        $keys = VpnManager::generateWireguardKeys();
        $wgPriv = $keys['private'];
        $wgPub  = $keys['public'];
    }

    db_execute("INSERT INTO tunnels (user_id, server_id, client_name, vpn_username, vpn_password, tunnel_ip, protocol, wireguard_private_key, wireguard_public_key, api_user, api_password, api_port, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'offline')",
                'iisssssssssi', [
                    $userId, $serverId, $clientName, $vpnUsername, $vpnPassword,
                    $tunnelIp, $protocol, $wgPriv, $wgPub, $apiUser, $apiPassword, $apiPort
                ]);

    $newId = db_last_id();

    // Otomatis buatkan port forward Winbox (8291) default untuk router ini
    $server = db_fetch_one("SELECT * FROM servers WHERE id = ?", 'i', [$serverId]);
    if ($server) {
        $autoWinboxPort = get_next_available_public_port((int)$server['min_port'], (int)$server['max_port']);
        db_execute("INSERT INTO port_forwards (tunnel_id, label, public_port, target_ip, target_port, protocol, is_enabled)
                    VALUES (?, 'Winbox Remote', ?, ?, 8291, 'tcp', 1)",
                    'iisi', [$newId, $autoWinboxPort, $tunnelIp]);

        if ($server['engine_type'] === 'linux_iptables') {
            NatManager::applyLinuxNatRule($server['public_ip'], $autoWinboxPort, $tunnelIp, 8291, 'tcp');
        }
    }

    flash_set('success', "Router tunnel '{$clientName}' berhasil dibuat!");
    header("Location: ../index.php?page=tunnels&action=script&id={$newId}");
    exit;
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $tunnel = db_fetch_one("SELECT t.*, s.public_ip, s.engine_type FROM tunnels t JOIN servers s ON t.server_id = s.id WHERE t.id = ?", 'i', [$id]);

    if (!$tunnel || (!$isAdmin && (int)$tunnel['user_id'] !== $userId)) {
        flash_set('danger', 'Tunnel tidak ditemukan atau Anda tidak memiliki hak akses.');
        header("Location: ../index.php?page=tunnels");
        exit;
    }

    // Hapus semua NAT rule port forward yang terkait di firewall
    $ports = db_fetch_all("SELECT * FROM port_forwards WHERE tunnel_id = ?", 'i', [$id]);
    foreach ($ports as $p) {
        if ($tunnel['engine_type'] === 'linux_iptables') {
            NatManager::removeLinuxNatRule($tunnel['public_ip'], (int)$p['public_port'], $p['target_ip'], (int)$p['target_port'], $p['protocol']);
        }
    }

    db_execute("DELETE FROM port_forwards WHERE tunnel_id = ?", 'i', [$id]);
    db_execute("DELETE FROM tunnels WHERE id = ?", 'i', [$id]);

    flash_set('success', "Router tunnel '{$tunnel['client_name']}' dan seluruh port terkait berhasil dihapus.");
    header("Location: ../index.php?page=tunnels");
    exit;
}

header("Location: ../index.php?page=tunnels");
exit;

